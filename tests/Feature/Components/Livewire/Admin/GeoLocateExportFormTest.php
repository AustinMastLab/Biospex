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

use App\Jobs\GeoLocateExportJob;
use App\Livewire\Admin\GeoLocateExportForm;
use App\Models\Expedition;
use App\Models\GeoLocateForm;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use App\Services\Actor\GeoLocate\GeoLocateFormService;
use Illuminate\Support\Facades\Bus;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mockery\MockInterface;

/**
 * GeoLocateFormService::getFormData() for a form, without the CSV files on S3.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function geoLocateFormData(array $overrides = []): array
{
    return array_merge([
        'group_id' => test()->group->id,
        'name' => '',
        'source' => 'reconciled',
        'entries' => 1,
        'fields' => null,
        'user_reconciled' => false,
        'expert_reconciled' => false,
        'expert_review' => false,
        'exported' => false,
        'geo' => ['County*', 'Country*', 'Locality*', 'ScientificName*', 'StateProvince*', 'Latitude'],
        'csv' => ['county', 'country', 'locality', 'scientific_name', 'state', 'lat'],
        'created_at' => '',
    ], $overrides);
}

/**
 * The form's rows with every required GeoLocate field matched to its CSV column.
 *
 * @return array<int, array{geo: string, csv: string}>
 */
function completeGeoLocateFields(): array
{
    return [
        ['geo' => 'County', 'csv' => 'county'],
        ['geo' => 'Country', 'csv' => 'country'],
        ['geo' => 'Locality', 'csv' => 'locality'],
        ['geo' => 'ScientificName', 'csv' => 'scientific_name'],
        ['geo' => 'StateProvince', 'csv' => 'state'],
    ];
}

/**
 * The form for test()->expedition, with the service returning $form (and $formAfter for later reads).
 *
 * @param  array<string, mixed>  $form
 */
function geoLocateExportForm(array $form = [], ?Closure $expectations = null): Testable
{
    test()->mock(GeoLocateFormService::class, function (MockInterface $service) use ($form, $expectations) {
        $service->shouldReceive('loadExpeditionRelations');
        $service->shouldReceive('getFormData')->byDefault()->andReturn(geoLocateFormData($form));
        $service->shouldReceive('checkExportDisabled')->byDefault()->andReturn(false);
        $expectations?->__invoke($service);
    });

    return Livewire::test(GeoLocateExportForm::class, ['expedition' => test()->expedition]);
}

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);
    $this->user->assignGroup($this->group);
    $this->expedition = Expedition::factory()->for(Project::factory()->create(['group_id' => $this->group->id]))->create();
    $this->actingAs($this->user);
});

it('starts a new form with the required GeoLocate fields', function () {
    geoLocateExportForm()
        ->assertSet('fields', array_map(fn (string $geo) => ['geo' => $geo, 'csv' => ''], GeoLocateExportForm::REQUIRED_FIELDS))
        ->assertSet('hasSavedForm', false)
        ->assertDontSeeHtml('id="export"');
});

it('shows the expedition\'s saved form, with Export', function () {
    geoLocateExportForm(['name' => 'Herbarium', 'fields' => completeGeoLocateFields()])
        ->assertSet('name', 'Herbarium')
        ->assertSet('fields', completeGeoLocateFields())
        ->assertSeeHtml('id="export"');
});

it('offers only the CSV sources the expedition has', function (array $flags, array $offered, array $notOffered) {
    $form = geoLocateExportForm($flags);

    foreach ($offered as $source) {
        $form->assertSeeHtml('value="'.$source.'"');
    }
    foreach ($notOffered as $source) {
        $form->assertDontSeeHtml('value="'.$source.'"');
    }
})->with([
    'reconciled only' => [[], ['reconciled'], ['reconciled-with-expert', 'reconciled-with-user']],
    'user reviewed' => [['user_reconciled' => true], ['reconciled', 'reconciled-with-user'], ['reconciled-with-expert']],
    'expert review published' => [['expert_review' => true, 'expert_reconciled' => true], ['reconciled', 'reconciled-with-expert'], ['reconciled-with-user']],
    'expert review not published' => [['expert_review' => true], ['reconciled'], ['reconciled-with-expert']],
]);

it('adds and removes rows, keeping at least one', function () {
    $form = geoLocateExportForm(['fields' => [['geo' => 'County', 'csv' => 'county']]])
        ->call('removeField')
        ->assertCount('fields', 1)
        ->call('addField')
        ->assertSet('fields.1', ['geo' => '', 'csv' => ''])
        ->call('removeField')
        ->assertCount('fields', 1);
});

it('saves the form for the expedition, then closes the modal', function () {
    geoLocateExportForm([], fn (MockInterface $service) => $service->shouldReceive('saveForm')->once()
        ->withArgs(fn (array $request, Expedition $expedition) => $request === [
            'group_id' => test()->group->id,
            'name' => 'Herbarium',
            'source' => 'reconciled',
            'fields' => completeGeoLocateFields(),
        ] && $expedition->is(test()->expedition)))
        ->set('name', 'Herbarium')
        ->set('fields', completeGeoLocateFields())
        ->call('save')
        ->assertHasNoErrors()
        ->assertJs("closeModal('global-modal')");
});

it('does not save a form with a duplicate or missing GeoLocate field, or an unmatched row', function (array $fields, string $message) {
    geoLocateExportForm([], fn (MockInterface $service) => $service->shouldNotReceive('saveForm'))
        ->set('name', 'Herbarium')
        ->set('fields', $fields)
        ->call('save')
        ->assertSee($message)
        ->assertNoJs();
})->with([
    'duplicate' => [[...completeGeoLocateFields(), ['geo' => 'County', 'csv' => 'lat']], 'GeoLocate Export fields may not contain duplicate values.'],
    'missing required' => [array_slice(completeGeoLocateFields(), 0, 4), 'GeoLocate requires the fields: StateProvince'],
    'no CSV column' => [[...completeGeoLocateFields(), ['geo' => 'Latitude', 'csv' => '']], 'Choose a CSV column in every row.'],
    'unknown CSV column' => [[...array_slice(completeGeoLocateFields(), 0, 4), ['geo' => 'StateProvince', 'csv' => 'not_a_column']], 'The selected CSV column is invalid.'],
]);

it('starts from another of the group\'s saved forms, but not another group\'s', function () {
    $saved = GeoLocateForm::factory()->create(['group_id' => $this->group->id, 'name' => 'Saved']);
    $otherGroups = GeoLocateForm::factory()->create();

    geoLocateExportForm([], fn (MockInterface $service) => $service->shouldReceive('getFormData')
        ->with(Mockery::type(Expedition::class), ['formId' => $saved->id, 'source' => 'reconciled'])
        ->andReturn(geoLocateFormData(['name' => 'Saved', 'fields' => [['geo' => 'Latitude', 'csv' => 'lat']]])))
        ->set('formId', (string) $saved->id)
        ->assertSet('name', 'Saved')
        ->assertSet('fields', [['geo' => 'Latitude', 'csv' => 'lat']])
        ->set('formId', (string) $otherGroups->id)
        ->assertSet('formId', '')
        ->assertSet('name', 'Saved');
});

it('reads the new source\'s columns, clearing chosen columns it doesn\'t have', function () {
    geoLocateExportForm(['user_reconciled' => true, 'fields' => completeGeoLocateFields()], fn (MockInterface $service) => $service->shouldReceive('getFormData')
        ->with(Mockery::type(Expedition::class), ['source' => 'reconciled-with-user'])
        ->andReturn(geoLocateFormData(['csv' => ['county', 'country']])))
        ->set('source', 'reconciled-with-user')
        ->assertSet('csvOptions', ['county', 'country'])
        ->assertSet('fields.0.csv', 'county')
        ->assertSet('fields.2.csv', '');
});

it('exports the saved form, then closes the modal', function () {
    Bus::fake([GeoLocateExportJob::class]);

    geoLocateExportForm(['fields' => completeGeoLocateFields()])
        ->call('export')
        ->assertJs("closeModal('global-modal')");

    Bus::assertDispatched(GeoLocateExportJob::class);
});

it('does not export without a saved form, or for a user outside the group', function () {
    Bus::fake([GeoLocateExportJob::class]);

    geoLocateExportForm()->call('export')->assertJs(
        'window.notify("exclamation-circle", '.json_encode(t('Save the form before exporting.')).', "warning")'
    );

    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    geoLocateExportForm(['fields' => completeGeoLocateFields()])->call('export');

    Bus::assertNotDispatched(GeoLocateExportJob::class);
});

it('says why the form can\'t load, such as no reconciled CSV yet', function () {
    geoLocateExportForm([], fn (MockInterface $service) => $service->shouldReceive('getFormData')->andThrow(new RuntimeException('File not found.')))
        ->assertSee('File not found.')
        ->assertDontSeeHtml('id="geolocate-form"');
});

it('loads into the global modal', function () {
    geoLocateExportForm();

    $this->get(route('admin.geolocate-form.index', [$this->expedition]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSeeLivewire(GeoLocateExportForm::class);
});
