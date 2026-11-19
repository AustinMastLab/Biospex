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

use App\Jobs\GridExportCsvJob;
use App\Livewire\Admin\SubjectGrid;
use App\Models\Expedition;
use App\Models\Header;
use App\Models\Project;
use App\Models\Subject;
use App\Models\User;
use App\Services\Grid\SubjectGridColumns;
use App\Services\Grid\SubjectSelection;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Subject::truncate();
    $this->actingAs($this->user = User::factory()->create());
});

afterEach(function () {
    Subject::truncate();
});

/**
 * A project whose import header has an image field and an occurrence field.
 */
function gridProject(): Project
{
    $project = Project::factory()->create();
    Header::create(['project_id' => $project->id, 'header' => ['image' => ['accessURI'], 'occurrence' => ['scientificName']]]);

    return $project;
}

function gridSubject(Project $project, string $imageId, array $attributes = []): Subject
{
    Subject::create([
        'project_id' => $project->id,
        'imageId' => $imageId,
        'expedition_ids' => [],
        'exported' => false,
        'accessURI' => 'https://example.org/'.$imageId.'.jpg',
        'ocr' => '',
        'occurrence' => ['scientificName' => 'Rumex hastatulus'],
        ...$attributes,
    ]);

    // Read back: the model create() returns doesn't carry its new id.
    return Subject::where('project_id', $project->id)->where('imageId', $imageId)->sole();
}

/**
 * The filter key for a column: "c" plus its position in the project's columns.
 */
function filterKey(Project $project, string $column): string
{
    return 'filters.c'.array_search($column, app(SubjectGridColumns::class)->all($project->id), true);
}

it('lists the project\'s subjects in the default columns, and not other projects\'', function () {
    $project = gridProject();
    gridSubject($project, 'fern-1');
    gridSubject(gridProject(), 'other-project-1');

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->assertSet('columns', ['assigned', 'exported', 'imageId', 'accessURI', 'ocr'])
        ->assertSee('fern-1')
        ->assertDontSee('other-project-1')
        ->assertSee('Showing 1 to 1 of 1 entries');
});

it('filters a column by the text it contains, matching special characters literally', function () {
    $project = gridProject();
    gridSubject($project, 'fern (pressed)');
    gridSubject($project, 'moss-2');

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->set(filterKey($project, 'imageId'), '(PRESSED')
        ->assertSee('fern (pressed)')
        ->assertDontSee('moss-2');
});

it('shows only unassigned subjects on the create form until the assigned filter changes', function () {
    $project = gridProject();
    gridSubject($project, 'free-1');
    gridSubject($project, 'taken-1', ['expedition_ids' => [99]]);

    Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->assertSee('free-1')
        ->assertDontSee('taken-1')
        ->set('assigned', 'all')
        ->assertSee('taken-1');
});

it('shows only the expedition\'s subjects on its page', function () {
    $project = gridProject();
    $expedition = Expedition::factory()->for($project)->create();
    gridSubject($project, 'in-expedition', ['expedition_ids' => [$expedition->id]]);
    gridSubject($project, 'elsewhere');

    Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'show', 'expedition' => $expedition])
        ->assertSee('in-expedition')
        ->assertDontSee('elsewhere');
});

it('starts the edit form with the expedition\'s subjects chosen and keeps changes on the server', function () {
    $project = gridProject();
    $expedition = Expedition::factory()->for($project)->create();
    $chosen = gridSubject($project, 'in-expedition', ['expedition_ids' => [$expedition->id]]);
    $free = gridSubject($project, 'free-1');

    $grid = Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'edit', 'expedition' => $expedition, 'selectable' => true])
        ->assertSee('Subjects selected')
        ->call('toggleSubject', (string) $free->id, true)
        ->call('toggleSubject', (string) $chosen->id, false);

    expect(app(SubjectSelection::class)->ids($this->user->id, $grid->get('selectionToken')))->toBe([(string) $free->id]);
});

it('chooses every subject on the page at once', function () {
    $project = gridProject();
    gridSubject($project, 'free-1');
    gridSubject($project, 'free-2');

    $grid = Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->call('togglePage', true);

    expect(app(SubjectSelection::class)->count($this->user->id, $grid->get('selectionToken')))->toBe(2);
});

it('remembers each user\'s columns and their order per project, and resets them', function () {
    $project = gridProject();
    gridSubject($project, 'fern-1');

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->call('toggleColumn', 'occurrence.scientificName')
        ->call('toggleColumn', 'exported')
        ->call('moveColumn', 'occurrence.scientificName', 0)
        ->assertSee('Rumex hastatulus');

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->assertSet('columns', ['occurrence.scientificName', 'assigned', 'imageId', 'accessURI', 'ocr'])
        ->call('resetColumns')
        ->assertSet('columns', ['assigned', 'exported', 'imageId', 'accessURI', 'ocr']);

    $this->actingAs(User::factory()->create());
    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->assertSet('columns', ['assigned', 'exported', 'imageId', 'accessURI', 'ocr']);
});

it('queues the CSV export of the filtered subjects', function () {
    Queue::fake();
    $project = gridProject();

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->set(filterKey($project, 'imageId'), 'fern')
        ->call('export');

    Queue::assertPushed(GridExportCsvJob::class, fn (GridExportCsvJob $job) => $job->data['projectId'] === $project->id
        && $job->data['criteria']['mode'] === 'explore'
        && $job->data['criteria']['filters'] === ['imageId' => 'fern']);
});

it('matches either word when a filter says "or"', function () {
    $project = gridProject();
    gridSubject($project, 'fl-1', ['ocr' => 'Collected in Florida']);
    gridSubject($project, 'ga-1', ['ocr' => 'GEORGIA, Ware County']);
    gridSubject($project, 'al-1', ['ocr' => 'Alabama']);

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->set(filterKey($project, 'ocr'), 'florida or georgia')
        ->assertSee('fl-1')
        ->assertSee('ga-1')
        ->assertDontSee('al-1');
});

it('matches every word joined by "and", in any order', function () {
    $project = gridProject();
    gridSubject($project, 'both-1', ['ocr' => 'Pensacola Beach, Escambia County']);
    gridSubject($project, 'one-1', ['ocr' => 'Escambia River']);
    gridSubject($project, 'none-1', ['ocr' => 'Alachua']);

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->set(filterKey($project, 'ocr'), 'Escambia and Pensacola Beach')
        ->assertSee('both-1')
        ->assertDontSee('one-1')
        ->assertDontSee('none-1');
});

it('lets "and" bind tighter than "or"', function () {
    $project = gridProject();
    gridSubject($project, 'beach-1', ['ocr' => 'Escambia, on the beach']);
    gridSubject($project, 'river-1', ['ocr' => 'Escambia River']);
    gridSubject($project, 'santa-1', ['ocr' => 'Santa Rosa County']);

    Livewire::test(SubjectGrid::class, ['project' => $project])
        ->set(filterKey($project, 'ocr'), 'escambia and beach or santa rosa')
        ->assertSee('beach-1')
        ->assertSee('santa-1')
        ->assertDontSee('river-1');
});

it('shows only the chosen subjects, or only the others, on the expedition form', function () {
    $project = gridProject();
    $chosen = gridSubject($project, 'chosen-1');
    gridSubject($project, 'other-1');

    Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->call('toggleSubject', (string) $chosen->id, true)
        ->set('assigned', 'chosen')
        ->assertSee('chosen-1')
        ->assertDontSee('other-1')
        ->set('assigned', 'unchosen')
        ->assertSee('other-1')
        ->assertDontSee('chosen-1');
});

it('selects every subject matching the filters, on every page', function () {
    $project = gridProject();
    foreach (range(1, 30) as $number) {
        gridSubject($project, sprintf('fern-%02d', $number));
    }
    gridSubject($project, 'moss-1');

    $grid = Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->set(filterKey($project, 'imageId'), 'fern')
        ->assertSee('Select all 30')
        ->call('selectAllMatching')
        ->assertSee('Subjects selected');

    expect(app(SubjectSelection::class)->count($this->user->id, $grid->get('selectionToken')))->toBe(30);
});

it('fills up to the expedition limit when some subjects are already selected', function () {
    config(['config.expedition_size' => 3]);
    $project = gridProject();
    $first = gridSubject($project, 'fern-1');
    gridSubject($project, 'fern-2');
    gridSubject($project, 'fern-3');

    $grid = Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->call('toggleSubject', (string) $first->id, true)
        ->call('toggleSubject', (string) gridSubject($project, 'moss-1')->id, true)
        ->set(filterKey($project, 'imageId'), 'fern')
        ->call('selectAllMatching')
        ->assertSee('Only 1 more subjects can be selected');

    expect(app(SubjectSelection::class)->count($this->user->id, $grid->get('selectionToken')))->toBe(3);
});

it('offers no select all when more subjects match than an expedition can hold', function () {
    config(['config.expedition_size' => 2]);
    $project = gridProject();
    foreach (range(1, 3) as $number) {
        gridSubject($project, 'fern-'.$number);
    }

    $grid = Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'create', 'selectable' => true])
        ->assertSee('Filter them to 2 or fewer to select them all at once.')
        ->call('selectAllMatching');

    expect(app(SubjectSelection::class)->count($this->user->id, $grid->get('selectionToken')))->toBe(0);
});

it('says so instead of offering choices once the expedition has started', function () {
    $project = gridProject();
    $expedition = Expedition::factory()->for($project)->create();

    Livewire::test(SubjectGrid::class, ['project' => $project, 'mode' => 'edit', 'expedition' => $expedition, 'selectable' => true, 'started' => true])
        ->assertSet('selectable', false)
        ->assertSee('This Expedition has started, so its Subjects can no longer change.');
});
