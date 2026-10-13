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

use App\Models\Import;
use App\Notifications\Generic;
use App\Services\DarwinCore\DwcBatchProcessor;
use App\Services\Process\CreateReportService;
use App\Services\Project\ProjectService;
use App\Traits\ButtonTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Notification;
use Throwable;

/**
 * Darwin Core Batch Import Job
 *
 * Enhanced queue job with batch processing, improved error handling,
 * and better user notifications with import statistics.
 */
class DwcBatchImportJob implements ShouldQueue
{
    use ButtonTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 14400; // 4 hours for large datasets

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2; // Reduced for large imports

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(protected Import $import)
    {
        $this->import = $import->withoutRelations();
        $this->onQueue(config('config.queue.import'));
    }

    /**
     * Safely get the number of attempts, handling JobNotFoundException.
     *
     * @return int Returns the attempt count or -1 if unavailable
     */
    private function safeGetAttempts(): int
    {
        try {
            return $this->attempts();
        } catch (Throwable $e) {
            Log::warning('Unable to retrieve job attempt count', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            return -1;
        }
    }

    public function handle(
        ProjectService $projectService,
        DwcBatchProcessor $batchProcessor,
        CreateReportService $createReportService
    ): void {
        $startTime = microtime(true);
        $scratchFileDir = Storage::disk('efs')->path(config('config.scratch_dir').'/'.md5($this->import->file));
        $importFilePath = Storage::disk('efs')->path($this->import->file);

        $project = $projectService->getProjectForDarwinImportJob($this->import->project_id);
        $users = $project->group->users->push($project->group->owner);

        // Check if this import is already being processed or completed
        $this->import->refresh();
        if ($this->import->processing == 1) {
            Log::warning('Import already being processed, skipping duplicate execution', [
                'import_id' => $this->import->id,
                'attempt' => $this->safeGetAttempts(),
            ]);
            $this->delete();

            return;
        }

        // Mark as processing to prevent duplicate execution
        $this->import->update(['processing' => 1, 'error' => 0]);

        try {
            // Create scratch directory with proper permissions
            $this->makeDirectory($scratchFileDir);

            // Extract archive with enhanced error checking
            $this->unzipArchive($importFilePath, $scratchFileDir);

            // Process with new batch processor
            $result = $batchProcessor->processArchive($this->import->project_id, $scratchFileDir);

            // Generate comprehensive reports
            $reports = $this->generateReports($createReportService, $batchProcessor);

            // Calculate processing time
            $processingTime = (float) number_format(microtime(true) - $startTime, 2, '.', '');

            // Send success notification with detailed statistics
            $this->sendSuccessNotification($users, $project, $result, $reports, $processingTime);

            // Trigger OCR processing
            TesseractOcrCreateJob::dispatch($project);

            // Cleanup files
            $this->cleanupFiles($scratchFileDir, $importFilePath);

            // Mark processing as complete before deletion
            $this->import->update(['processing' => 0]);
            $this->import->delete();
            $this->delete();

        } catch (Throwable $throwable) {
            $this->handleImportFailure($throwable, $users, $project, $scratchFileDir, $startTime);
        }
    }

    /**
     * Handle job failure with enhanced error reporting and safe attempt counting.
     */
    public function failed(Throwable $exception): void
    {
        try {
            $attemptCount = $this->safeGetAttempts();

            Log::error('Darwin Core batch import job failed permanently', [
                'import_id' => $this->import->id,
                'project_id' => $this->import->project_id,
                'attempts' => $attemptCount,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Mark import as failed and reset processing flag
            $this->import->error = 1;
            $this->import->processing = 0;
            $this->import->save();

        } catch (Throwable $failureException) {
            // Fallback logging when even the failed method encounters issues
            Log::error('Critical error in failed() method - unable to process job failure', [
                'import_id' => $this->import->id ?? 'unknown',
                'original_error' => $exception->getMessage(),
                'failure_error' => $failureException->getMessage(),
            ]);

            // Still try to mark import as failed if possible
            try {
                $this->import->error = 1;
                $this->import->processing = 0;
                $this->import->save();
            } catch (Throwable $saveException) {
                Log::critical('Unable to save import failure state', [
                    'import_id' => $this->import->id ?? 'unknown',
                    'save_error' => $saveException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Create directory with proper error handling.
     */
    private function makeDirectory(string $dir): void
    {
        if (File::exists($dir)) {
            File::cleanDirectory($dir);
        } elseif (! File::makeDirectory($dir, 0775, true)) {
            throw new Exception(t('Unable to create directory: :directory', [':directory' => $dir]));
        }

        if (! File::isWritable($dir) && ! chmod($dir, 0775)) {
            throw new Exception(t('Unable to make directory writable: %s', $dir));
        }
    }

    /**
     * Enhanced archive extraction with error checking.
     */
    private function unzipArchive(string $zipFile, string $dir): void
    {
        if (! file_exists($zipFile)) {
            throw new Exception("Archive file not found: {$zipFile}");
        }

        // Use shell_exec with error checking
        $command = "unzip -q \"$zipFile\" -d \"$dir\" 2>&1";
        $output = shell_exec($command);
        $exitCode = shell_exec('echo $?');

        if ($exitCode != 0) {
            throw new Exception("Failed to extract archive. Output: {$output}");
        }

        // Verify meta.xml exists
        if (! file_exists($dir.'/meta.xml')) {
            throw new Exception('Invalid Darwin Core Archive: meta.xml not found');
        }
    }

    /**
     * Generate comprehensive reports.
     */
    private function generateReports(CreateReportService $createReportService, DwcBatchProcessor $batchProcessor): array
    {
        $reports = [];

        // Generate duplicates report
        $duplicates = $batchProcessor->getDuplicates();
        if (! empty($duplicates)) {
            $dupsCsvName = md5($this->import->id).'_duplicates.csv';
            $dupName = $createReportService->createCsvReport($dupsCsvName, $duplicates);

            if ($dupName) {
                $reports['duplicates'] = [
                    'name' => $dupName,
                    'route' => route('admin.downloads.report', ['file' => $dupName]),
                    'count' => count($duplicates),
                ];
            }
        }

        // Generate rejected records report
        $rejected = $batchProcessor->getRejectedMedia();
        if (! empty($rejected)) {
            $rejCsvName = md5($this->import->id).'_rejected.csv';
            $rejName = $createReportService->createCsvReport($rejCsvName, $rejected);

            if ($rejName) {
                $reports['rejected'] = [
                    'name' => $rejName,
                    'route' => route('admin.downloads.report', ['file' => $rejName]),
                    'count' => count($rejected),
                ];
            }
        }

        return $reports;
    }

    /**
     * Send enhanced success notification.
     */
    private function sendSuccessNotification(
        $users,
        $project,
        array $result,
        array $reports,
        $processingTime
    ): void {
        $buttons = [];

        // Add report download buttons
        if (isset($reports['duplicates'])) {
            $buttons = array_merge($buttons, $this->createButton(
                $reports['duplicates']['route'],
                t('View Duplicate Records (%d)', $reports['duplicates']['count'])
            ));
        }

        if (isset($reports['rejected'])) {
            $buttons = array_merge($buttons, $this->createButton(
                $reports['rejected']['route'],
                t('View Rejected Records (%d)', $reports['rejected']['count']),
                'error'
            ));
        }

        $htmlMessages = [
            t('The Darwin Core import for %s has been completed successfully.', $project->title),
            '',
            '<strong>'.t('Import Statistics:').'</strong>',
            t('• Subjects created: %d', $result['subjects_created']),
            t('• Processing time: %s seconds', $processingTime),
        ];

        if (isset($reports['duplicates'])) {
            $htmlMessages[] = t('• Duplicate records: %d', $reports['duplicates']['count']);
        }

        if (isset($reports['rejected'])) {
            $htmlMessages[] = t('• Rejected records: %d', $reports['rejected']['count']);
        }

        $htmlMessages[] = '';
        $htmlMessages[] = t('OCR processing may take longer and you will receive an email when it is complete.');

        $attributes = [
            'subject' => t('Darwin Core Import Complete - %s', $project->title),
            'html' => $htmlMessages,
            'buttons' => $buttons,
        ];

        Notification::send($users, new Generic($attributes));
    }

    /**
     * Handle import failure with comprehensive error reporting and safe attempt counting.
     */
    private function handleImportFailure(
        Throwable $throwable,
        $users,
        $project,
        string $scratchFileDir,
        float $startTime
    ): void {
        $processingTime = (float) number_format(microtime(true) - $startTime, 2, '.', '');
        $attemptCount = $this->safeGetAttempts();

        Log::error('Darwin Core batch import failed', [
            'import_id' => $this->import->id,
            'project_id' => $this->import->project_id,
            'attempt' => $attemptCount,
            'max_attempts' => $this->tries,
            'processing_time' => $processingTime.'s',
            'error' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ]);

        // Mark import as failed if this is the last attempt (or if attempt count is unavailable)
        if ($attemptCount >= $this->tries || $attemptCount === -1) {
            try {
                $this->import->error = 1;
                $this->import->processing = 0;
                $this->import->save();
            } catch (Throwable $saveException) {
                Log::error('Failed to mark import as failed', [
                    'import_id' => $this->import->id,
                    'save_error' => $saveException->getMessage(),
                ]);
            }
        } else {
            // Reset processing flag for retry
            try {
                $this->import->processing = 0;
                $this->import->save();
            } catch (Throwable $saveException) {
                Log::error('Failed to reset processing flag for retry', [
                    'import_id' => $this->import->id,
                    'save_error' => $saveException->getMessage(),
                ]);
            }
        }

        // Cleanup scratch directory
        $this->cleanupScratchDirectory($scratchFileDir);

        // Send failure notification only on final failure
        if ($attemptCount >= $this->tries || $attemptCount === -1) {
            try {
                $this->sendFailureNotification($users, $project, $throwable, $processingTime);
            } catch (Throwable $notificationException) {
                Log::error('Failed to send failure notification', [
                    'import_id' => $this->import->id,
                    'notification_error' => $notificationException->getMessage(),
                ]);
            }
        }

        // Only delete job on final failure or if attempt count is unavailable
        if ($attemptCount >= $this->tries || $attemptCount === -1) {
            try {
                $this->delete();
            } catch (Throwable $deleteException) {
                Log::error('Failed to delete job', [
                    'import_id' => $this->import->id,
                    'delete_error' => $deleteException->getMessage(),
                ]);
            }
        } else {
            // Release job for retry
            try {
                $this->release($this->backoff);
            } catch (Throwable $releaseException) {
                Log::error('Failed to release job for retry', [
                    'import_id' => $this->import->id,
                    'release_error' => $releaseException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send comprehensive failure notification.
     */
    private function sendFailureNotification(
        $users,
        $project,
        Throwable $throwable,
        $processingTime
    ): void {
        $attributes = [
            'subject' => t('Darwin Core Import Failed - %s', $project->title),
            'html' => [
                t('An error occurred while importing the Darwin Core Archive.'),
                '',
                '<strong>'.t('Error Details:').'</strong>',
                t('• Project: %s', $project->title),
                t('• Project ID: %s', $project->id),
                t('• Processing time: %s seconds', $processingTime),
                t('• Error: %s', $throwable->getMessage()),
                t('• File: %s', basename($throwable->getFile())),
                t('• Line: %s', $throwable->getLine()),
                '',
                t('The Administration has been notified. If you are unable to resolve this issue, please contact the Administration.'),
            ],
        ];

        Notification::send($users, new Generic($attributes, true));
    }

    /**
     * Clean up files after successful import.
     */
    private function cleanupFiles(string $scratchFileDir, string $importFilePath): void
    {
        $this->cleanupScratchDirectory($scratchFileDir);

        if (file_exists($importFilePath)) {
            File::delete($importFilePath);
        }
    }

    /**
     * Clean up scratch directory.
     */
    private function cleanupScratchDirectory(string $scratchFileDir): void
    {
        if (File::isDirectory($scratchFileDir) && ! File::deleteDirectory($scratchFileDir)) {
            Log::warning('Could not remove the import scratch directory', [
                'import_id' => $this->import->id,
                'directory' => $scratchFileDir,
            ]);
        }
    }
}
