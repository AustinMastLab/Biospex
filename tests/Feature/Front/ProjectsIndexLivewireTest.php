<?php

use App\Livewire\Front\ProjectsIndex;
use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('public_sort:projects:version');
    Storage::fake('s3');
});

it('initial render uses default ordering (date asc)', function () {
    $old = Project::factory()->create(['created_at' => now()->subDays(2), 'title' => 'Old']);
    $new = Project::factory()->create(['created_at' => now(), 'title' => 'New']);
    PanoptesProject::factory()->create(['project_id' => $old->id]);
    PanoptesProject::factory()->create(['project_id' => $new->id]);

    Livewire::test(ProjectsIndex::class)
        ->assertSet('sort', 'date')
        ->assertSet('order', 'asc')
        ->assertSee('Old')
        ->assertSee('New')
        ->assertSeeInOrder(['Old', 'New']);
});

it('clicking Title sorts by title asc, clicking Title again toggles to desc', function () {
    $a = Project::factory()->create(['title' => 'Alpha']);
    $z = Project::factory()->create(['title' => 'Zebra']);
    PanoptesProject::factory()->create(['project_id' => $a->id]);
    PanoptesProject::factory()->create(['project_id' => $z->id]);

    Livewire::test(ProjectsIndex::class)
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Alpha', 'Zebra'])
        ->call('sortBy', 'title')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Zebra', 'Alpha']);
});

it('clicking Group sorts by group asc; switching sort resets order to asc', function () {
    $gA = Group::factory()->create(['title' => 'A Group']);
    $gB = Group::factory()->create(['title' => 'B Group']);
    $p1 = Project::factory()->for($gB)->create(['title' => 'Project 1']);
    $p2 = Project::factory()->for($gA)->create(['title' => 'Project 2']);
    PanoptesProject::factory()->create(['project_id' => $p1->id]);
    PanoptesProject::factory()->create(['project_id' => $p2->id]);

    Livewire::test(ProjectsIndex::class)
        ->call('sortBy', 'group')
        ->assertSet('sort', 'group')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Project 2', 'Project 1'])
        ->call('sortBy', 'group')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Project 1', 'Project 2'])
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc');
});

it('clicking Date sorts by date asc; toggling works', function () {
    $old = Project::factory()->create(['created_at' => now()->subDays(2), 'title' => 'Old']);
    $new = Project::factory()->create(['created_at' => now(), 'title' => 'New']);
    PanoptesProject::factory()->create(['project_id' => $old->id]);
    PanoptesProject::factory()->create(['project_id' => $new->id]);

    Livewire::test(ProjectsIndex::class)
        // Default is date asc, so first click on date should toggle to desc
        ->call('sortBy', 'date')
        ->assertSet('sort', 'date')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['New', 'Old'])
        ->call('sortBy', 'date')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Old', 'New']);
});

it('calls ProjectService::getPublicIndexCachedData', function () {
    $p = Project::factory()->create([
        'title' => 'Mocked Project',
    ]);
    PanoptesProject::factory()->create(['project_id' => $p->id]);

    $service = new ProjectService(
        app(\App\Models\Project::class),
        app(\App\Models\ProjectAsset::class),
        app(\App\Services\Helpers\CountService::class),
        app(\App\Services\Helpers\DateService::class)
    );
    $data = $service->getPublicIndex(['sort' => 'date', 'order' => 'asc']);

    $mock = $this->mock(ProjectService::class);
    $mock->shouldReceive('getPublicIndexCachedData')
        ->once()
        ->with(['sort' => 'date', 'order' => 'asc'])
        ->andReturn($data);

    Livewire::test(ProjectsIndex::class)
        ->assertSee('Mocked Project');
});
