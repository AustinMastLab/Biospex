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

use App\Models\Actor;
use App\Models\Expedition;
use App\Models\ExpeditionStat;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowManager;
use App\Services\Subject\SubjectService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['group_id' => $this->group->id]);
    $this->user->assignGroup(Group::factory()->create(['title' => config('config.admin.group', 'Admin')]));
    $this->actingAs($this->user);
});

/**
 * @return array<string, mixed>
 */
function expeditionFormData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Expedition Save Test',
        'description' => 'Subjects are saved with bulk updates',
        'keywords' => 'test',
        'workflow_id' => Workflow::factory()->create()->id,
        'subject-ids' => '',
    ], $overrides);
}

/**
 * MongoDB lives outside the test process, so the subject service is mocked.
 *
 * @param  array<int, string>  $currentIds  Subjects assigned before the save.
 */
function mockSubjects(array $currentIds, int $assignedCount): MockInterface
{
    return test()->mock(SubjectService::class, function (MockInterface $mock) use ($currentIds, $assignedCount) {
        $mock->shouldReceive('getIdsByExpeditionId')->andReturn(collect($currentIds));
        $mock->shouldReceive('countByExpeditionId')->andReturn($assignedCount);
    });
}

function existingExpedition(Project $project, array $attributes = []): Expedition
{
    $expedition = Expedition::factory()->for($project)->create($attributes);
    ExpeditionStat::factory()->for($expedition, 'expedition')->create();

    return $expedition;
}

it('creates an expedition and attaches its subjects in the same request', function () {
    $actor = Actor::factory()->create();
    $workflow = Workflow::factory()->create();
    $workflow->actors()->attach($actor, ['order' => 1]);

    mockSubjects([], 2)
        ->shouldReceive('detachSubjects')->once()->withArgs(fn ($ids) => $ids->isEmpty())
        ->getMock()
        ->shouldReceive('attachSubjects')->once()->withArgs(fn ($ids) => $ids->values()->all() === ['a1', 'b2']);

    $response = $this->post(route('admin.expeditions.store', $this->project), expeditionFormData([
        'workflow_id' => $workflow->id,
        'subject-ids' => ' a1,b2,,a1 ',
    ]));

    $expedition = Expedition::where('title', 'Expedition Save Test')->sole();
    $response->assertRedirect(route('admin.expeditions.show', $expedition))
        ->assertSessionHas('success', t('Record was created successfully.'));

    expect($expedition->stat->local_subject_count)->toBe(2)
        ->and($expedition->actors()->sole()->pivot->total)->toBe(2);
});

it('detaches removed subjects and attaches added ones on update', function () {
    $expedition = existingExpedition($this->project);

    mockSubjects(['a1', 'b2'], 2)
        ->shouldReceive('detachSubjects')->once()->withArgs(fn ($ids, $id) => $ids->values()->all() === ['a1'] && $id === $expedition->id)
        ->getMock()
        ->shouldReceive('attachSubjects')->once()->withArgs(fn ($ids, $id) => $ids->values()->all() === ['c3'] && $id === $expedition->id);

    $this->put(route('admin.expeditions.update', $expedition), expeditionFormData(['subject-ids' => 'b2,c3']))
        ->assertRedirect(route('admin.expeditions.show', $expedition))
        ->assertSessionHas('success', t('Record was updated successfully.'));

    expect($expedition->stat()->sole()->local_subject_count)->toBe(2);
});

it('leaves subjects unchanged once a workflow manager exists', function () {
    $expedition = existingExpedition($this->project);
    WorkflowManager::factory()->create(['expedition_id' => $expedition->id]);

    mockSubjects(['a1'], 1)->shouldNotReceive('detachSubjects', 'attachSubjects');

    $this->put(route('admin.expeditions.update', $expedition), expeditionFormData(['subject-ids' => 'z9']))
        ->assertSessionHas('success');

    expect($expedition->stat()->sole()->local_subject_count)->toBe(1);
});

it('keeps a new expedition and asks to resubmit when its subjects fail to save', function () {
    mockSubjects([], 0)
        ->shouldReceive('detachSubjects')
        ->getMock()
        ->shouldReceive('attachSubjects')->andThrow(new RuntimeException('Mongo unavailable'));

    $response = $this->post(route('admin.expeditions.store', $this->project), expeditionFormData(['subject-ids' => 'a1']));

    $expedition = Expedition::where('title', 'Expedition Save Test')->sole();
    $response->assertRedirect(route('admin.expeditions.edit', $expedition))
        ->assertSessionHas('danger', t('The Expedition was saved, but its subjects could not be. Please submit the form again.'));
});

it('keeps updated details and asks to resubmit when subjects fail to save', function () {
    $expedition = existingExpedition($this->project);

    mockSubjects([], 0)
        ->shouldReceive('detachSubjects')
        ->getMock()
        ->shouldReceive('attachSubjects')->andThrow(new RuntimeException('Mongo unavailable'));

    $this->put(route('admin.expeditions.update', $expedition), expeditionFormData(['title' => 'Renamed Expedition', 'subject-ids' => 'a1']))
        ->assertRedirect(route('admin.expeditions.edit', $expedition))
        ->assertSessionHas('danger', t('The Expedition was saved, but its subjects could not be. Please submit the form again.'));

    expect($expedition->fresh()->title)->toBe('Renamed Expedition');
});

it('rejects more subjects than an expedition allows without saving anything', function () {
    config(['config.expedition_size' => 2]);
    mockSubjects([], 0)->shouldNotReceive('attachSubjects', 'detachSubjects');

    $this->post(route('admin.expeditions.store', $this->project), expeditionFormData(['subject-ids' => 'a1,b2,c3']))
        ->assertSessionHasErrors('subject-ids');

    expect(Expedition::count())->toBe(0);
});
