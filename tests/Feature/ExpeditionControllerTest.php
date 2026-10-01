<?php

use App\Jobs\ExpeditionSaveJob;
use App\Models\Expedition;
use App\Models\ExpeditionSaveRequest;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Queue;

const EXPEDITION_SAVE_SUBMITTED_MESSAGE = 'Expedition data was saved. Subject assignment is being processed, and you will be notified by email when it is complete.';

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);
    $this->user->assignGroup($this->group);
    $this->project = Project::factory()->create(['group_id' => $this->group->id]);
    $this->workflow = Workflow::factory()->create();

    $this->actingAs($this->user);
});

function expeditionSavePayload(Workflow $workflow, array $overrides = []): array
{
    return array_merge([
        'title' => 'Queued Expedition',
        'description' => 'Expedition description',
        'keywords' => 'plants,herbarium',
        'workflow_id' => $workflow->id,
        'subject-ids' => '507f1f77bcf86cd799439011,507f1f77bcf86cd799439012',
        'locked' => 1,
    ], $overrides);
}

it('queues subject assignment after creating an expedition', function () {
    Queue::fake([ExpeditionSaveJob::class]);

    $response = $this->post(
        route('admin.expeditions.store', $this->project),
        expeditionSavePayload($this->workflow)
    );

    $response->assertRedirect()
        ->assertSessionHas('success', t(EXPEDITION_SAVE_SUBMITTED_MESSAGE));

    $expedition = Expedition::query()->where('title', 'Queued Expedition')->firstOrFail();

    expect($expedition->project_id)->toBe($this->project->id);
    expect($expedition->stat)
        ->not->toBeNull()
        ->local_subject_count->toBe(0);

    $this->get(route('admin.expeditions.show', $expedition))
        ->assertSee(t('Biospex Subjects'));

    $saveRequest = ExpeditionSaveRequest::query()
        ->where('expedition_id', $expedition->id)
        ->firstOrFail();

    expect($saveRequest)
        ->operation->toBe('create')
        ->subject_ids->toBe([
            '507f1f77bcf86cd799439011',
            '507f1f77bcf86cd799439012',
        ]);

    Queue::assertPushed(ExpeditionSaveJob::class, function (ExpeditionSaveJob $job): bool {
        return $job->queue === config('config.queue.expedition_save');
    });
});

it('queues subject assignment after updating an expedition', function () {
    Queue::fake([ExpeditionSaveJob::class]);

    $expedition = Expedition::factory()->create([
        'project_id' => $this->project->id,
        'workflow_id' => $this->workflow->id,
    ]);

    $response = $this->put(
        route('admin.expeditions.update', $expedition),
        expeditionSavePayload($this->workflow, [
            'title' => 'Updated Expedition',
            'subject-ids' => '507f1f77bcf86cd799439013',
        ])
    );

    $response->assertRedirect()
        ->assertSessionHas('success', t(EXPEDITION_SAVE_SUBMITTED_MESSAGE));

    $expedition->refresh();

    expect($expedition->title)->toBe('Updated Expedition');

    $saveRequest = ExpeditionSaveRequest::query()
        ->where('expedition_id', $expedition->id)
        ->firstOrFail();

    expect($saveRequest)
        ->operation->toBe('update')
        ->subject_ids->toBe(['507f1f77bcf86cd799439013']);

    Queue::assertPushed(ExpeditionSaveJob::class);
});

it('does not queue subject assignment when expedition validation fails', function () {
    Queue::fake([ExpeditionSaveJob::class]);

    $response = $this->post(route('admin.expeditions.store', $this->project), []);

    $response->assertSessionHasErrors(['title', 'description', 'keywords', 'workflow_id']);

    Queue::assertNothingPushed();
    expect(ExpeditionSaveRequest::query()->count())->toBe(0);
});

it('does not queue subject assignment for a user outside the project group', function () {
    Queue::fake([ExpeditionSaveJob::class]);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($otherUser);

    $response = $this->post(
        route('admin.expeditions.store', $this->project),
        expeditionSavePayload($this->workflow)
    );

    $response->assertRedirect(route('admin.projects.index'));

    Queue::assertNothingPushed();
    expect(ExpeditionSaveRequest::query()->count())->toBe(0);
});
