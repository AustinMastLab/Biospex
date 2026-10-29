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

namespace App\Services\Process;

use App\Models\Download;
use App\Models\ExportQueue;
use App\Services\Csv\AwsS3CsvService;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;

/**
 * Class CreateReportService
 */
class CreateReportService
{
    /**
     * Construct.
     */
    public function __construct(protected AwsS3CsvService $awsS3CsvService, protected Download $download) {}

    /**
     * Create csv report.
     *
     * @throws CannotInsertRecord|Exception
     */
    public function createCsvReport(string $csvName, array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        [$header, $rows] = $this->awsS3CsvService->csv->alignRows($data);

        $bucket = config('filesystems.disks.s3.bucket');
        $filePath = config('config.report_dir').'/'.$csvName;

        $this->awsS3CsvService->createBucketStream($bucket, $filePath, 'w');
        $this->awsS3CsvService->createCsvWriterFromStream();
        $this->awsS3CsvService->csv->insertOne($header);
        $this->awsS3CsvService->csv->insertAll($rows);

        return base64_encode($csvName);
    }

    /**
     * Save report.
     */
    public function saveReport(ExportQueue $exportQueue, string $csvName)
    {
        $attributes = [
            'expedition_id' => $exportQueue->expedition_id,
            'actor_id' => $exportQueue->actor_id,
            'type' => 'report',
        ];
        $values = [
            'expedition_id' => $exportQueue->expedition_id,
            'actor_id' => $exportQueue->actor_id,
            'file' => $csvName,
            'type' => 'report',
        ];

        $this->download->updateOrCreate($attributes, $values);
    }
}
