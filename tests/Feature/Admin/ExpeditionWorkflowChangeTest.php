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

use App\Enums\ActorExpeditionState;
use App\Models\Actor;
use App\Models\Expedition;
use App\Models\ExpeditionStat;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Subject\SubjectService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['group_id' => $this->group->id]);
    $this->user->assignGroup(Group::factory()->create(['title' => config('config.admin.group', 'Admin')]));
    $this->actingAs($this->user);

    $this->zooniverseActor = Actor::factory()->create(['title' => 'Zooniverse']);
    $this->geoLocateActor = Actor::factory()->create(['title' => 'GeoLocate']);
    $this->zooniverseWorkflow = Workflow::factory()->create(['enabled' => 1]);
    $this->zooniverseWorkflow->actors()->attach($this->zooniverseActor, ['order' => 1]);
    $this->geoLocateWorkflow = Workflow::factory()->create(['enabled' => 1]);
    $this->geoLocateWorkflow->actors()->attach($this->zooniverseActor, ['order' => 1]);
    $this->geoLocateWorkflow->actors()->attach($this->geoLocateActor, ['order' => 2]);

    config([
        'zooniverse.actor_id' => $this->zooniverseActor->id,
        'geolocate.actor_id' => $this->geoLocateActor->id,
        'geolocate.workflow_id' => $this->geoLocateWorkflow->id,
    ]);

    // MongoDB lives outside the test process, so the subjects are mocked.
    $this->mock(SubjectService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getIdsByExpeditionId')->andReturn(collect());
        $mock->shouldReceive('countByExpeditionId')->andReturn(0);
        $mock->shouldReceive('detachSubjects', 'attachSubjects');
    });
});

/**
 * An expedition on the Zooniverse workflow, with its Zooniverse actor in the given state.
 */
function zooniverseExpedition(array $attributes = [], ActorExpeditionState $state = ActorExpeditionState::Complete): Expedition
{
    $expedition = Expedition::factory()->for(test()->project)->create(array_merge([
        'workflow_id' => test()->zooniverseWorkflow->id,
        'completed' => 1,
        'locked' => 0,
    ], $attributes));
    ExpeditionStat::factory()->for($expedition, 'expedition')->create();
    $expedition->actors()->attach(test()->zooniverseActor->id, [
        'state' => $state->value,
        'total' => 0,
        'error' => 0,
        'order' => 1,
        'expert' => 0,
    ]);

    return $expedition;
}

/**
 * @return array<string, mixed>
 */
function workflowFormData(Expedition $expedition, int $workflowId, array $overrides = []): array
{
    return array_merge([
        'title' => $expedition->title.' updated',
        'description' => 'Changing the workflow to GeoLocate',
        'keywords' => 'test',
        'workflow_id' => $workflowId,
        'subject-ids' => '',
    ], $overrides);
}

it('changes a completed expedition to GeoLocate once, locking and reopening it', function () {
    $expedition = zooniverseExpedition();

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $this->geoLocateWorkflow->id));

    $response->assertSessionHas('success');
    $expedition->refresh();
    expect($expedition->workflow_id)->toBe($this->geoLocateWorkflow->id)
        ->and($expedition->locked)->toBe(1)
        ->and($expedition->completed)->toBe(0)
        ->and($expedition->actors()->pluck('actors.id')->all())->toContain($this->geoLocateActor->id);
});

it('refuses the change to GeoLocate when the expedition is locked', function () {
    $expedition = zooniverseExpedition(['locked' => 1]);

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $this->geoLocateWorkflow->id));

    $response->assertSessionHasErrors('workflow_id');
    expect($expedition->fresh()->workflow_id)->toBe($this->zooniverseWorkflow->id);
});

it('refuses the change to GeoLocate before Zooniverse is complete', function () {
    $expedition = zooniverseExpedition(['completed' => 0], ActorExpeditionState::Processing);

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $this->geoLocateWorkflow->id));

    $response->assertSessionHasErrors('workflow_id');
    expect($expedition->fresh()->workflow_id)->toBe($this->zooniverseWorkflow->id);
});

it('refuses the change to GeoLocate when the GeoLocate workflow is disabled', function () {
    $this->geoLocateWorkflow->update(['enabled' => 0]);
    $expedition = zooniverseExpedition();

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $this->geoLocateWorkflow->id));

    $response->assertSessionHasErrors('workflow_id');
    expect($expedition->fresh()->workflow_id)->toBe($this->zooniverseWorkflow->id);
});

it('refuses changing to a workflow other than GeoLocate', function () {
    $other = Workflow::factory()->create(['enabled' => 1]);
    $expedition = zooniverseExpedition();

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $other->id));

    $response->assertSessionHasErrors('workflow_id');
    expect($expedition->fresh()->workflow_id)->toBe($this->zooniverseWorkflow->id);
});

it('does not lock an expedition saved without changing its workflow', function () {
    $expedition = zooniverseExpedition();

    $response = $this->put(route('admin.expeditions.update', $expedition), workflowFormData($expedition, $this->zooniverseWorkflow->id, ['locked' => 1]));

    $response->assertSessionHas('success');
    expect($expedition->fresh())
        ->locked->toBe(0)
        ->completed->toBe(1);
});

it('creates an unlocked expedition', function () {
    $response = $this->post(route('admin.expeditions.store', $this->project), [
        'title' => 'New Unlocked Expedition',
        'description' => 'Created without a lock',
        'keywords' => 'test',
        'workflow_id' => $this->zooniverseWorkflow->id,
        'locked' => 1,
        'subject-ids' => '',
    ]);

    $response->assertSessionHas('success');
    expect(Expedition::where('title', 'New Unlocked Expedition')->sole()->locked)->toBe(0);
});

it('creates a new expedition on the GeoLocate workflow with both actors', function () {
    $response = $this->post(route('admin.expeditions.store', $this->project), [
        'title' => 'New GeoLocate Expedition',
        'description' => 'Starts on GeoLocate',
        'keywords' => 'test',
        'workflow_id' => $this->geoLocateWorkflow->id,
        'subject-ids' => '',
    ]);

    $response->assertSessionHas('success');
    $expedition = Expedition::where('title', 'New GeoLocate Expedition')->sole();
    expect($expedition->workflow_id)->toBe($this->geoLocateWorkflow->id)
        ->and($expedition->locked)->toBe(0)
        ->and($expedition->actors()->pluck('actors.id')->sort()->values()->all())
        ->toBe(collect([$this->zooniverseActor->id, $this->geoLocateActor->id])->sort()->values()->all());
});

it('offers GeoLocate on the edit page only when the workflow can change to it', function () {
    Storage::fake('s3');
    $ready = zooniverseExpedition();
    $locked = zooniverseExpedition(['locked' => 1]);

    $readyPage = $this->get(route('admin.expeditions.edit', $ready));
    $lockedPage = $this->get(route('admin.expeditions.edit', $locked));

    $readyPage->assertOk()
        ->assertViewHas('canChangeToGeoLocate', true)
        ->assertViewHas('workflowOptions', fn (array $options) => array_keys($options) === [$this->zooniverseWorkflow->id, $this->geoLocateWorkflow->id]);
    $lockedPage->assertOk()
        ->assertViewHas('canChangeToGeoLocate', false)
        ->assertViewHas('workflowOptions', fn (array $options) => array_keys($options) === [$this->zooniverseWorkflow->id]);
});
