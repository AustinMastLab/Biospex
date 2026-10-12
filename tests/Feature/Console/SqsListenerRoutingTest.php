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

use App\Console\Commands\SqsListenerBatchUpdate;
use App\Console\Commands\SqsListenerExportUpdate;
use App\Console\Commands\SqsListenerImageDlq;
use App\Console\Commands\SqsListenerOcrUpdate;
use App\Console\Commands\SqsListenerReconcileUpdate;
use App\Jobs\LabelReconciliationJob;
use App\Jobs\TesseractOcrUpdateJob;
use App\Jobs\ZooniverseExportBatchResultJob;
use App\Jobs\ZooniverseExportImageUpdateJob;
use App\Jobs\ZooniverseExportZipResultJob;
use App\Models\ExportQueue;
use App\Services\SqsListenerService;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    Queue::fake();
    $this->sqs = $this->mock(SqsListenerService::class);
});

/**
 * A listener command ready to route messages, with its console output captured.
 *
 * @template T of Command
 *
 * @param  class-string<T>  $class
 * @return T
 */
function sqsListener(string $class): Command
{
    $command = app($class);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    return $command;
}

/**
 * Read a protected property of a queued job.
 */
function jobProperty(object $job, string $property): mixed
{
    return (fn () => $this->{$property})->call($job);
}

describe('batch:listen', function () {
    it('sends a finished batch to ZooniverseExportBatchResultJob', function () {
        sqsListener(SqsListenerBatchUpdate::class)->routeMessage(['function' => 'BiospexBatchCreator', 'status' => 'success', 'downloadId' => 7]);

        Queue::assertPushed(ZooniverseExportBatchResultJob::class);
    });

    it('raises a failed batch so the listener alerts', function () {
        expect(fn () => sqsListener(SqsListenerBatchUpdate::class)->routeMessage(['function' => 'BiospexBatchCreator', 'status' => 'failed', 'downloadId' => 7, 'error' => 'timeout']))
            ->toThrow(RuntimeException::class, 'Batch export failed for download #7: timeout');
        Queue::assertNothingPushed();
    });
});

describe('export:listen', function () {
    it('sends every processed image to ZooniverseExportImageUpdateJob, failed or not', function (string $status) {
        sqsListener(SqsListenerExportUpdate::class)->routeMessage(['function' => 'BiospexImageProcess', 'status' => $status, 'fileId' => 5, 'queueId' => 3, 'subjectId' => 'abc']);

        Queue::assertPushed(ZooniverseExportImageUpdateJob::class, fn ($job) => jobProperty($job, 'status') === $status);
    })->with(['success', 'failed']);

    it('sends a ready zip to ZooniverseExportZipResultJob', function (string $function) {
        sqsListener(SqsListenerExportUpdate::class)->routeMessage(['function' => $function, 'status' => 'zip-ready', 'queueId' => 3]);

        Queue::assertPushed(ZooniverseExportZipResultJob::class);
    })->with(['BiospexZipCreator', 'BiospexZipMerger']);

    it('waits for the remaining parts of a batched zip', function () {
        sqsListener(SqsListenerExportUpdate::class)->routeMessage(['function' => 'BiospexZipCreator', 'status' => 'partial-zip-ready', 'queueId' => 3]);

        Queue::assertNothingPushed();
    });

    it('ignores the empty-batch message from the Step Function', function () {
        sqsListener(SqsListenerExportUpdate::class)->routeMessage(['function' => 'BiospexZipCreator', 'status' => 'zip-failed', 'queueId' => 3, 'error' => 'No files found for range']);

        Queue::assertNothingPushed();
    });

    it('marks the export as failed and raises a real zip failure', function () {
        $queue = ExportQueue::factory()->create(['error' => 0]);

        expect(fn () => sqsListener(SqsListenerExportUpdate::class)->routeMessage(['function' => 'BiospexZipCreator', 'status' => 'zip-failed', 'queueId' => $queue->id, 'error' => 'disk full']))
            ->toThrow(RuntimeException::class, "Zip export failed for export #{$queue->id}: disk full");

        expect($queue->fresh())->error->toBe(1)->error_message->toBe('disk full');
        Queue::assertNothingPushed();
    });
});

describe('reconcile:listen', function () {
    it('sends a finished reconciliation to LabelReconciliationJob', function () {
        sqsListener(SqsListenerReconcileUpdate::class)->routeMessage(['function' => 'BiospexLabelReconciliation', 'status' => 'success', 'expeditionId' => 12]);

        Queue::assertPushed(LabelReconciliationJob::class);
    });

    it('alerts about a failed reconciliation without raising, so the message is deleted', function () {
        $this->sqs->shouldReceive('handleError')->once()->withArgs(fn (string $message) => $message === 'Label reconciliation failed for expedition #12: bad csv');

        sqsListener(SqsListenerReconcileUpdate::class)->routeMessage(['function' => 'BiospexLabelReconciliation', 'status' => 'failed', 'expeditionId' => 12, 'error' => 'bad csv']);

        Queue::assertNothingPushed();
    });
});

describe('ocr:listen', function () {
    it('sends every OCR result to TesseractOcrUpdateJob', function (array $id) {
        sqsListener(SqsListenerOcrUpdate::class)->routeMessage([...$id, 'status' => 'success', 'queueId' => 4, 'text' => 'Quercus']);

        Queue::assertPushed(TesseractOcrUpdateJob::class);
    })->with(['by file' => [['fileId' => 9]], 'by subject' => [['subjectId' => 'abc']]]);

    it('rejects an OCR result without a file or subject', function () {
        expect(fn () => sqsListener(SqsListenerOcrUpdate::class)->routeMessage(['status' => 'success']))
            ->toThrow(InvalidArgumentException::class);
        Queue::assertNothingPushed();
    });
});

describe('image:listen-dlq', function () {
    it('marks a dead-lettered export image as failed', function () {
        sqsListener(SqsListenerImageDlq::class)->routeMessage(['taskType' => 'export', 'fileId' => 5, 'queueId' => 3, 'subjectId' => 'abc']);

        Queue::assertPushed(ZooniverseExportImageUpdateJob::class, fn ($job) => jobProperty($job, 'status') === 'failed'
            && jobProperty($job, 'error') === 'DLQ: Message exceeded maximum retries in SQS');
    });

    it('marks the subject of a crashed OCR Lambda as failed', function () {
        sqsListener(SqsListenerImageDlq::class)->routeMessage(['Records' => [['s3' => ['object' => ['key' => 'ocr/123/subjectABC.jpg']]]]]);

        Queue::assertPushed(TesseractOcrUpdateJob::class, fn ($job) => jobProperty($job, 'subjectId') === 'subjectABC'
            && jobProperty($job, 'status') === 'failed');
    });
});

it('rejects messages it cannot route', function (string $listener, array $message) {
    expect(fn () => sqsListener($listener)->routeMessage($message))->toThrow(InvalidArgumentException::class);
    Queue::assertNothingPushed();
})->with([
    'batch: no function' => [SqsListenerBatchUpdate::class, ['status' => 'success']],
    'batch: unknown function' => [SqsListenerBatchUpdate::class, ['function' => 'Nope', 'status' => 'success']],
    'export: unknown function' => [SqsListenerExportUpdate::class, ['function' => 'Nope', 'status' => 'success']],
    'export: image without file or subject' => [SqsListenerExportUpdate::class, ['function' => 'BiospexImageProcess', 'status' => 'success']],
    'reconcile: unknown function' => [SqsListenerReconcileUpdate::class, ['function' => 'Nope', 'status' => 'success']],
    'reconcile: no expedition' => [SqsListenerReconcileUpdate::class, ['function' => 'BiospexLabelReconciliation', 'status' => 'success']],
    'dlq: unknown task type' => [SqsListenerImageDlq::class, ['taskType' => 'nope']],
]);
