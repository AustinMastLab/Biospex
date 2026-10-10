<?php

namespace App\Jobs;

use App\Models\ExportQueueFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to update the status of image export processing and trigger CSV build when complete.
 *
 * This job handles updating individual export queue file statuses and checks if the entire
 * export process is complete to trigger the next stage of processing.
 */
class ZooniverseExportImageUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $fileId;

    public int $queueId;

    public string $subjectId;

    public string $status;

    public ?string $error;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  {
     *
     * @type int $queueId The ID of the export queue
     * @type string $subjectId The subject ID for the export
     * @type string $status The processing status
     * @type string $error Optional. Error message if processing failed
     *              }
     */
    public function __construct(array $data)
    {
        $this->fileId = isset($data['fileId']) ? (int) $data['fileId'] : null;
        $this->queueId = (int) $data['queueId'];
        $this->subjectId = (string) $data['subjectId'];
        $this->status = (string) $data['status'];
        $this->error = $data['error'] ?? null;

        $this->onQueue(config('config.queue.export'));
    }

    /**
     * Execute the job.
     *
     * Updates the processing status of an export queue file and checks if the export is complete.
     */
    public function handle(): void
    {
        // Primary lookup by ID if available, otherwise fallback to legacy lookup
        $file = $this->fileId
            ? ExportQueueFile::find($this->fileId)
            : ExportQueueFile::where('queue_id', $this->queueId)
                ->where('subject_id', $this->subjectId)
                ->first();

        if (! $file) {
            Log::warning('ZooniverseExportImageUpdateJob: File not found', [
                'file_id' => $this->fileId,
                'queue_id' => $this->queueId,
                'subject_id' => $this->subjectId,
            ]);

            return;
        }

        // If the fetcher reported a failure, log the error message
        if ($this->status !== 'success') {
            $file->message = $this->error ?? 'Processing failed';
        }

        $file->processed = 1;
        $file->tries = $file->tries + 1;
        $file->save();
    }
}
