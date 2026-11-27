<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use App\Jobs\PanoptesProjectUpdateJob;
use App\Livewire\Admin\GeoLocateCommunityForm;
use App\Livewire\Admin\PanoptesWorkflowForm;
use App\Livewire\Admin\ReconcileUserUpload;
use App\Models\Expedition;
use App\Models\GeoLocateCommunity;
use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\User;
use App\Services\Actor\GeoLocate\GeoLocateStatService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A signed-in member of a group, and an expedition of one of the group's projects.
 *
 * @return array{0: User, 1: Expedition}
 */
function groupMemberWithExpedition(): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create(['user_id' => $user->id]);
    $user->assignGroup($group);
    $expedition = Expedition::factory()->for(Project::factory()->create(['group_id' => $group->id]))->create();

    test()->actingAs($user);

    return [$user, $expedition];
}

/**
 * The window.notify() call a component makes through App\Traits\WithModalNotices.
 */
function modalNotice(string $message, string $type): string
{
    $icon = $type === 'success' ? 'check-circle' : 'exclamation-circle';

    return 'window.notify('.json_encode($icon).', '.json_encode($message).', '.json_encode($type).')';
}

describe('Edit Workflow Id', function () {
    it('saves the workflow id, updates the project from Zooniverse, and closes the modal', function () {
        Bus::fake([PanoptesProjectUpdateJob::class]);
        [, $expedition] = groupMemberWithExpedition();

        Livewire::test(PanoptesWorkflowForm::class, ['expedition' => $expedition])
            ->assertSet('panoptesWorkflowId', '')
            ->set('panoptesWorkflowId', '12345')
            ->call('save')
            ->assertHasNoErrors()
            ->assertJs(modalNotice(t('Workflow id is updated.'), 'success'))
            ->assertJs("closeModal('global-modal')");

        expect(PanoptesProject::where('expedition_id', $expedition->id)->value('panoptes_workflow_id'))->toBe(12345);
        Bus::assertDispatched(PanoptesProjectUpdateJob::class);
    });

    it('shows the saved workflow id, and asks for a number', function () {
        Bus::fake([PanoptesProjectUpdateJob::class]);
        [, $expedition] = groupMemberWithExpedition();
        PanoptesProject::factory()->create(['project_id' => $expedition->project_id, 'expedition_id' => $expedition->id, 'panoptes_workflow_id' => 777]);

        Livewire::test(PanoptesWorkflowForm::class, ['expedition' => $expedition])
            ->assertSet('panoptesWorkflowId', '777')
            ->set('panoptesWorkflowId', 'abc')
            ->call('save')
            ->assertHasErrors(['panoptesWorkflowId' => 'integer'])
            ->assertNoJs();

        Bus::assertNotDispatched(PanoptesProjectUpdateJob::class);
    });

    it('does not save for a user outside the expedition\'s group', function () {
        Bus::fake([PanoptesProjectUpdateJob::class]);
        $expedition = Expedition::factory()->create();
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

        Livewire::test(PanoptesWorkflowForm::class, ['expedition' => $expedition])
            ->set('panoptesWorkflowId', '12345')
            ->call('save')
            ->assertJs(modalNotice(t('You are not authorized for this action.'), 'warning'));

        expect(PanoptesProject::where('expedition_id', $expedition->id)->exists())->toBeFalse();
    });
});

describe('GeoLocate Community & Data Source', function () {
    it('asks for a new community\'s name only when New is chosen', function () {
        [, $expedition] = groupMemberWithExpedition();
        $community = GeoLocateCommunity::factory()->create(['project_id' => $expedition->project_id, 'name' => 'Ferns']);

        Livewire::test(GeoLocateCommunityForm::class, ['expedition' => $expedition])
            ->assertSee(t('New GeoLocate Community'))
            ->set('communityId', (string) $community->id)
            ->assertDontSee(t('New GeoLocate Community'))
            ->set('communityId', '')
            ->set('dataSource', 'herbarium')
            ->call('save')
            ->assertHasErrors(['community' => 'required'])
            ->assertSee(t('Enter a name for the new community.'));
    });

    it('saves a new community and data source, then closes the modal', function () {
        [, $expedition] = groupMemberWithExpedition();
        $this->mock(GeoLocateStatService::class)
            ->shouldReceive('saveCommunityDataSource')->once()
            ->withArgs(fn (array $data, Expedition $saved) => $data === ['community_id' => null, 'community' => 'Mosses', 'data_source' => 'herbarium']
                && $saved->is($expedition));

        Livewire::test(GeoLocateCommunityForm::class, ['expedition' => $expedition])
            ->set('community', 'Mosses')
            ->set('dataSource', 'herbarium')
            ->call('save')
            ->assertHasNoErrors()
            ->assertJs(modalNotice(t('Community and data source added.'), 'success'))
            ->assertJs("closeModal('global-modal')");
    });

    it('rejects another project\'s community, and keeps the modal open when GeoLocate refuses', function () {
        [, $expedition] = groupMemberWithExpedition();
        $community = GeoLocateCommunity::factory()->create(['project_id' => $expedition->project_id]);
        $otherCommunity = GeoLocateCommunity::factory()->create();
        $this->mock(GeoLocateStatService::class)
            ->shouldReceive('saveCommunityDataSource')->once()->andThrow(new RuntimeException('Data source not found.'));

        $form = Livewire::test(GeoLocateCommunityForm::class, ['expedition' => $expedition])
            ->set('communityId', (string) $otherCommunity->id)
            ->set('dataSource', 'herbarium')
            ->call('save')
            ->assertHasErrors(['communityId' => 'in']);

        $form->set('communityId', (string) $community->id)
            ->call('save')
            ->assertJs(modalNotice('Data source not found.', 'warning'));

        expect(collect($form->effects['xjs'] ?? [])->pluck('expression'))->not->toContain("closeModal('global-modal')");
    });
});

describe('Upload Reconciled With User Opinion', function () {
    it('stores the CSV as the expedition\'s reconciled with user file, then closes the modal', function () {
        Storage::fake('s3');
        [, $expedition] = groupMemberWithExpedition();

        Livewire::test(ReconcileUserUpload::class, ['expedition' => $expedition])
            ->set('file', UploadedFile::fake()->createWithContent('reviewed.csv', "subject_id,County\n1,Leon\n"))
            ->call('save')
            ->assertHasNoErrors()
            ->assertJs("closeModal('global-modal')");

        Storage::disk('s3')->assertExists(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv');
    });

    it('accepts only a CSV', function () {
        Storage::fake('s3');
        [, $expedition] = groupMemberWithExpedition();

        Livewire::test(ReconcileUserUpload::class, ['expedition' => $expedition])
            ->call('save')
            ->assertHasErrors(['file' => 'required'])
            ->set('file', UploadedFile::fake()->create('reviewed.xlsx', 1))
            ->call('save')
            ->assertHasErrors(['file' => 'extensions'])
            ->assertSee(t('File must be a CSV.'));

        Storage::disk('s3')->assertMissing(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv');
    });
});

it('loads each form into the global modal', function (string $route, string $component) {
    [, $expedition] = groupMemberWithExpedition();

    $this->get(route($route, [$expedition]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSeeLivewire($component);
})->with([
    'workflow id' => ['admin.panoptes-workflow.edit', PanoptesWorkflowForm::class],
    'community' => ['admin.geolocate-community.edit', GeoLocateCommunityForm::class],
    'reconcile upload' => ['admin.reconcile-user-form.edit', ReconcileUserUpload::class],
]);
