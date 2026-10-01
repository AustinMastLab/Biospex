<?php

use App\Jobs\ExpeditionSaveJob;
use App\Models\Expedition;
use App\Models\ExpeditionSaveRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Notifications\Generic;
use App\Services\Expedition\ExpeditionService;
use Illuminate\Support\Facades\Notification;

it('deletes the handoff request after completing subject assignment', function () {
    Notification::fake();

    $user = User::factory()->create();
    $expedition = Expedition::factory()
        ->for(Project::factory())
        ->for(Workflow::factory())
        ->create();
    $saveRequest = ExpeditionSaveRequest::factory()->create([
        'expedition_id' => $expedition->id,
        'user_id' => $user->id,
        'operation' => 'update',
        'subject_ids' => ['507f1f77bcf86cd799439011'],
    ]);

    $service = Mockery::mock(ExpeditionService::class);
    $service->shouldReceive('synchronizeSubjects')
        ->once()
        ->andReturn(1);
    $service->shouldReceive('syncStat')
        ->once();
    $service->shouldReceive('syncActors')
        ->once();

    (new ExpeditionSaveJob($expedition->id))->handle($service);

    expect(ExpeditionSaveRequest::query()->find($saveRequest->id))->toBeNull();
    Notification::assertSentTo($user, Generic::class);
});

it('retains the handoff request and notifies the user after terminal failure', function () {
    Notification::fake();

    $user = User::factory()->create();
    $expedition = Expedition::factory()
        ->for(Project::factory())
        ->for(Workflow::factory())
        ->create();
    $saveRequest = ExpeditionSaveRequest::factory()->create([
        'expedition_id' => $expedition->id,
        'user_id' => $user->id,
    ]);

    (new ExpeditionSaveJob($expedition->id))->failed(new RuntimeException('MongoDB unavailable'));

    $saveRequest->refresh();

    expect($saveRequest)
        ->status->toBe('failed')
        ->failed_at->not->toBeNull();

    Notification::assertSentTo($user, Generic::class);
});
