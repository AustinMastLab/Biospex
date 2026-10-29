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

namespace App\Services\Reconcile;

use App\Facades\TranscriptionMapHelper;
use App\Models\Download;
use App\Models\Expedition;
use App\Models\Reconcile;
use App\Notifications\Generic;
use App\Services\Csv\AwsS3CsvService;

/**
 * Class ExpertReconcilePublishService
 */
class ExpertReconcilePublishService
{
    /**
     * ExpertReconcilePublishService constructor.
     */
    public function __construct(
        protected Reconcile $reconcile,
        protected Download $download,
        protected AwsS3CsvService $awsS3CsvService
    ) {}

    /**
     * Publish reconciled file.
     *
     * @throws \Exception
     */
    public function publishReconciled(Expedition $expedition): void
    {
        $this->createReconcileCsv($expedition);
        $this->createDownload($expedition);
        $this->sendEmail($expedition);
    }

    /**
     * Create csv file for reconciled.
     *
     * @throws \Exception
     */
    private function createReconcileCsv(Expedition $expedition): void
    {
        $results = $this->reconcile->where('subject_expeditionId', (int) $expedition->id)->get();
        $mapped = $results->map(function ($record) {
            // The model exposes the key as `id`; unsetting `_id` would leave it in.
            unset($record->id, $record->subject_columns, $record->subject_problem, $record->updated_at, $record->created_at, $record->reviewed);

            return $record;
        });

        if ($mapped->isEmpty()) {
            throw new \Exception(t('Missing reconciled records for Expert Review publish for Expedition Id: %s', $expedition->id));
        }

        $header = array_keys($mapped->first()->toArray());
        $decodedHeader = [];
        foreach ($header as $value) {
            $decodedHeader[] = TranscriptionMapHelper::decodeTranscriptionField($value);
        }

        $file = config('zooniverse.directory.reconciled-with-expert').'/'.$expedition->id.'.csv';
        $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $file, 'w');
        $this->awsS3CsvService->createCsvWriterFromStream();
        $this->awsS3CsvService->csv->insertOne($decodedHeader);
        $this->awsS3CsvService->csv->insertAll($mapped->toArray());
    }

    /**
     * Create download file.
     */
    private function createDownload(Expedition $expedition): void
    {
        $values = [
            'expedition_id' => $expedition->id,
            'actor_id' => 2,
            'file' => $expedition->id.'.csv',
            'type' => 'reconciled-with-expert',
        ];
        $attributes = [
            'expedition_id' => $expedition->id,
            'actor_id' => 2,
            'file' => $expedition->id.'.csv',
            'type' => 'reconciled-with-expert',
        ];

        $this->download->updateOrCreate($attributes, $values);
    }

    /**
     * Send email to project owner.
     */
    private function sendEmail(Expedition $expedition): void
    {
        $attributes = [
            'subject' => t('Reconciled Expert Review Published'),
            'html' => [
                t('The Expert Reviewed Reconciled CSV file has been published for %s.', $expedition->title),
                t('The file can be downloaded in the Downloads section of the Expedition page.'),
            ],
        ];

        $expedition->project->group->owner->notify(new Generic($attributes));
    }
}
