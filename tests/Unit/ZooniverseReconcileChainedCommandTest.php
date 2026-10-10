<?php

use App\Models\Expedition;
use App\Services\Expedition\ExpeditionService;
use App\Services\Reconcile\ReconcileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('queues standard reconciliation without explanations', function () {
    $this->mock(ExpeditionService::class);

    $this->mock(ReconcileService::class)
        ->shouldReceive('sendToReconcileTriggerQueue')
        ->once()
        ->with(42, false);

    $this->artisan('zooniverse:reconcile-chain', ['expeditionIds' => [42]])
        ->assertExitCode(0);
});

it('skips expeditions marked skip_reconcile', function () {
    $this->mock(ExpeditionService::class);

    $skipped = Expedition::factory()->create(['skip_reconcile' => true]);
    $included = Expedition::factory()->create();

    $this->mock(ReconcileService::class)
        ->shouldReceive('sendToReconcileTriggerQueue')
        ->once()
        ->with($included->id, false);

    $this->artisan('zooniverse:reconcile-chain', ['expeditionIds' => [$skipped->id, $included->id]])
        ->assertExitCode(0);
});
