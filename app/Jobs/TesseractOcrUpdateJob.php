<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
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

namespace App\Jobs;

use App\Enums\LambdaStatus;
use App\Models\OcrQueueFile;
use App\Services\Subject\SubjectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to update OCR processing results for a subject.
 *
 * This job handles updating the OCR text results for subjects after processing,
 * managing the OCR queue status, and tracking completion of OCR batches.
 *
 * @implements ShouldQueue
 */
class TesseractOcrUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1500;

    protected int $fileId;

    protected int $queueId;

    protected string $subjectId;

    protected string $status;

    protected ?string $text;

    protected ?string $error;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  Array containing OCR processing results
     */
    public function __construct(array $data)
    {
        $this->fileId = (int) ($data['fileId'] ?? 0);
        $this->queueId = (int) ($data['queueId'] ?? 0);
        $this->subjectId = (string) ($data['subjectId'] ?? '');
        $this->status = (string) ($data['status'] ?? LambdaStatus::Failed->value);
        $this->text = $data['text'] ?? null;
        $this->error = $data['error'] ?? null;

        $this->onQueue(config('config.queue.ocr'));
    }

    /**
     * Execute the job to update OCR results.
     *
     * @param  SubjectService  $subjectService  Service to update subject data
     */
    public function handle(SubjectService $subjectService): void
    {
        // Simple lookup - no relationship needed for the main worker loop
        $file = $this->fileId > 0
            ? OcrQueueFile::find($this->fileId)
            : OcrQueueFile::where('subject_id', $this->subjectId)
                ->where('processed', 0)
                ->first();

        if (! $file) {
            Log::warning('TesseractOcrUpdateJob: File not found', [
                'file_id' => $this->fileId,
                'subject_id' => $this->subjectId,
            ]);

            return;
        }

        if (LambdaStatus::tryFrom($this->status) === LambdaStatus::Success) {
            $text = trim(preg_replace('/\s+/', ' ', $this->text));
            $text = $text !== '' ? $text : '[OCR produced no text]';
            $subjectService->update(['ocr' => $text], $this->subjectId);
        } else {
            $errorText = '[OCR Failed] '.($this->error ?? 'Unknown error');
            $subjectService->update(['ocr' => $errorText], $this->subjectId);
        }

        $file->processed = 1;
        $file->save();
    }

    /**
     * Handle a job failure.
     *
     * Uses eager loading to directly access and flag the parent queue as errored.
     */
    public function failed(Throwable $throwable): void
    {
        $file = $this->fileId > 0
            ? OcrQueueFile::with('ocrQueue')->find($this->fileId)
            : OcrQueueFile::with('ocrQueue')
                ->where('subject_id', $this->subjectId)
                ->where('processed', 0)
                ->first();

        if ($file && $file->ocrQueue) {
            $file->ocrQueue->error = 1;
            $file->ocrQueue->save();
        }
    }
}
