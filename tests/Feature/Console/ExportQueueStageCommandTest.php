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

use App\Enums\ExportQueueStage;
use App\Jobs\ZooniverseExportBuildCsvJob;
use App\Jobs\ZooniverseExportCreateReportJob;
use App\Jobs\ZooniverseExportDeleteFilesJob;
use App\Jobs\ZooniverseExportProcessImagesJob;
use App\Models\ExportQueue;
use App\Services\SupervisorControlService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    // app:export-stage starts the export listeners through sqs:control.
    $this->mock(SupervisorControlService::class)->shouldReceive('control');
});

it('restarts a failed export at the requested stage', function (int $stage, string $job) {
    $queue = ExportQueue::factory()->create(['stage' => ExportQueueStage::Waiting, 'queued' => 0, 'error' => 1]);

    $this->artisan('app:export-stage', ['queueId' => $queue->id, '--stage' => $stage])->assertSuccessful();

    Queue::assertPushed($job);
    expect($queue->fresh())
        ->stage->toBe(ExportQueueStage::from($stage))
        ->queued->toBe(1)
        ->error->toBe(0);
})->with([
    'processing images' => [1, ZooniverseExportProcessImagesJob::class],
    'building the CSV' => [2, ZooniverseExportBuildCsvJob::class],
    'creating the report' => [4, ZooniverseExportCreateReportJob::class],
    'deleting working files' => [5, ZooniverseExportDeleteFilesJob::class],
]);

it('restarts at the queue\'s current stage when no stage is given', function () {
    $queue = ExportQueue::factory()->create(['stage' => ExportQueueStage::CreatingReport, 'queued' => 0, 'error' => 1]);

    $this->artisan('app:export-stage', ['queueId' => $queue->id])->assertSuccessful();

    Queue::assertPushed(ZooniverseExportCreateReportJob::class);
});

it('rejects a stage outside 1 to 5 without touching the queue', function (?string $stage, ExportQueueStage $current) {
    $queue = ExportQueue::factory()->create(['stage' => $current, 'queued' => 0, 'error' => 1]);

    $this->artisan('app:export-stage', ['queueId' => $queue->id] + ($stage === null ? [] : ['--stage' => $stage]))
        ->expectsOutput('Stage must be between 1 and 5')
        ->assertFailed();

    Queue::assertNothingPushed();
    expect($queue->fresh())->queued->toBe(0)->error->toBe(1);
})->with([
    'stage 0' => ['0', ExportQueueStage::BuildingCsv],
    'stage 6' => ['6', ExportQueueStage::BuildingCsv],
    'a waiting queue with no stage given' => [null, ExportQueueStage::Waiting],
]);
