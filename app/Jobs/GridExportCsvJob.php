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

use App\Models\Header;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Csv\AwsS3CsvService;
use App\Services\Grid\JqGridEncoder;
use App\Services\Helpers\GeneralService;
use App\Traits\ButtonTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\CannotInsertRecord;
use Throwable;

/**
 * Class GridExportCsvJob
 */
class GridExportCsvJob implements ShouldQueue
{
    use ButtonTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800;

    private string $csvName;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user, public array $data)
    {
        $this->user = $user->withoutRelations();
        $this->onQueue(config('config.queue.default'));
        $this->csvName = Str::random().'.csv';
    }

    /**
     * Execute the job.
     *
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(
        JqGridEncoder $jqGridEncoder,
        AwsS3CsvService $awsS3CsvService,
        GeneralService $generalService): void
    {
        $cursor = $jqGridEncoder->encodeGridExportData($this->data);

        $header = $this->buildHeader();

        $bucket = config('filesystems.disks.s3.bucket');
        $filePath = config('config.report_dir').'/'.$this->csvName;

        $awsS3CsvService->createBucketStream($bucket, $filePath, 'w');
        $awsS3CsvService->createCsvWriterFromStream();
        $awsS3CsvService->csv->insertOne($header->keys()->toArray());

        $cursor->each(function ($subject) use ($header, $awsS3CsvService, $generalService) {
            $subjectArray = $subject->getAttributes();
            $subjectArray['_id'] = (string) $subject->id;
            $subjectArray['expedition_ids'] = trim(implode(', ', $subject->expedition_ids), ',');
            $subjectArray['updated_at'] = $subject->updated_at ? $subject->updated_at->toDateTimeString() : '';
            $subjectArray['created_at'] = $subject->created_at ? $subject->created_at->toDateTimeString() : '';
            $subjectArray['ocr'] = $generalService->forceUtf8($subject->ocr);
            $subjectArray['occurrence'] = $this->decodeAndEncode($subject['occurrence']->getAttributes());

            // Only header columns: the model exposes the key as `id`, and a subject
            // can carry fields the project header doesn't list.
            $merged = $header->merge(collect($subjectArray)->only($header->keys()));

            $awsS3CsvService->csv->insertOne($merged->toArray());
        });

        $awsS3CsvService->closeBucketStream();

        if (! Storage::disk('s3')->exists($filePath)) {
            throw new Exception(t('Csv export file is missing: %s', $filePath));
        }

        $route = route('admin.downloads.report', ['file' => base64_encode($this->csvName)]);
        $btn = $this->createButton($route, t('Download CSV'));
        $html = ! is_null($this->data['expeditionId']) ?
            t('Your grid export for Expedition Id %s is complete. Click the button provided to download:', $this->data['expeditionId']) :
            t('Your grid export for Project Id %s is complete. Click the button provided to download:', $this->data['projectId']);

        $attributes = [
            'subject' => t('Grid Export to CSV Complete'),
            'html' => [$html],
            'buttons' => $btn,
        ];

        $this->user->notify(new Generic($attributes));
    }

    /**
     * Build the header for export.
     */
    private function buildHeader(): Collection
    {
        $header = Header::where('project_id', $this->data['projectId'])->first()->header['image'];
        array_unshift($header, '_id', 'project_id', 'imageId', 'expedition_ids', 'exported');
        array_push($header, 'ocr', 'occurrence', 'updated_at', 'created_at');

        return collect($header)->flip()->map(function ($value, $key) {
            return '';
        });
    }

    /**
     * Decode fields from occurrence then encode to avoid errors.
     *
     * @return false|string
     */
    private function decodeAndEncode($occurrence)
    {
        unset($occurrence['_id'], $occurrence['updated_at'], $occurrence['created_at']);

        foreach ($occurrence as $field => $value) {
            if ($this->isJson($value)) {
                $value = json_decode($value);
            }

            $occurrence[$field] = $value;
        }

        return json_encode($occurrence);
    }

    /**
     * Check if value is json.
     */
    public function isJson($str): bool
    {
        $json = json_decode($str);

        return $json !== false && ! is_null($json) && $str != $json;
    }

    /**
     * The job failed to process.
     */
    public function failed(Throwable $throwable): void
    {
        $idMessage = ! is_null($this->data['expeditionId']) ?
            t('Expedition Id: %s', $this->data['expeditionId']) :
            t('Project Id: %s', $this->data['projectId']);

        $attributes = [
            'subject' => t('Grid Export to CSV Error'),
            'html' => [
                t('An error occurred during csv export from the grid.'),
                $idMessage,
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
                t('Message: %s', $throwable->getMessage()),
                t('Code: %s', $throwable->getTrace()),
                t('The Administration has been notified. If you are unable to resolve this issue, please contact the Administration.'),
            ],
        ];

        $this->user->notify(new Generic($attributes, true));
        Storage::disk('s3')->delete(config('config.report_dir').'/'.$this->csvName);
    }
}
