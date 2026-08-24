<?php

use App\Services\Expedition\ExpeditionService;
use App\Services\Reconcile\ReconcileService;
use Tests\TestCase;

uses(TestCase::class);

it('queues standard reconciliation without explanations', function () {
    $this->mock(ExpeditionService::class);

    $this->mock(ReconcileService::class)
        ->shouldReceive('sendToReconcileTriggerQueue')
        ->once()
        ->with(42, false);

    $this->artisan('zooniverse:reconcile-chain', ['expeditionIds' => [42]])
        ->assertExitCode(0);
});
