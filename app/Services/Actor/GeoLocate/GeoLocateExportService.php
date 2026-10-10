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

namespace App\Services\Actor\GeoLocate;

use App\Enums\ActorExpeditionState;
use App\Models\Download;
use App\Models\Expedition;
use App\Models\GeoLocateExport;
use App\Models\GeoLocateForm;
use App\Services\Csv\AwsS3CsvService;
use Exception;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use Throwable;

/**
 * Class GeoLocateExportService
 *
 * Service responsible for managing the GeoLocate export process,
 * including generating, migrating, moving, and deleting export CSV files,
 * as well as updating related records and associations.
 */
class GeoLocateExportService
{
    private string $csvFilePath;

    /**
     * Constructor for initializing the class with required dependencies.
     *
     * @param  GeoLocateExport  $geoLocateExport  An instance of GeoLocateExport for geographical data export operations.
     * @param  AwsS3CsvService  $awsS3CsvService  Service instance for handling AWS S3 and CSV-related operations.
     * @param  Download  $download  An instance for managing file download functionality.
     * @return void
     */
    public function __construct(
        protected GeoLocateExport $geoLocateExport,
        protected AwsS3CsvService $awsS3CsvService,
        protected Download $download,
    ) {}

    /**
     * Processes the given expedition by performing a series of operations such as migrating records,
     * building the expedition data, moving the CSV file, creating a download link, and updating the expedition's actor-pivot state.
     * Handles exceptions by cleaning up files and resetting states if any error occurs during the process.
     *
     * @param  Expedition  $expedition  The expedition to be processed.
     *
     * @throws CannotInsertRecord
     * @throws \League\Csv\Exception
     * @throws Throwable
     */
    public function process(Expedition $expedition): void
    {
        $this->setCsvFilePath($expedition->id);

        try {
            $this->migrateRecords($expedition);
            $this->build($expedition);
            $this->moveCsvFile();
            $this->createDownload($expedition);
            $this->updateActorExpeditionPivot($expedition);

        } catch (Throwable $throwable) {
            $expedition->actors()->updateExistingPivot(config('geolocate.actor_id'), [
                'state' => ActorExpeditionState::NotStarted->value,
            ]);

            $csvFilePath = $this->getCsvFilePath();

            if (Storage::disk('s3')->exists($this->csvFilePath)) {
                Storage::disk('s3')->delete($this->csvFilePath);
            }

            if (Storage::disk('efs')->exists($csvFilePath)) {
                Storage::disk('efs')->delete($csvFilePath);
            }

            throw $throwable;
        }
    }

    /**
     * Migrates records from a source file to the database using provided expedition data.
     *
     * @param  Expedition  $expedition  The expedition entity used to determine the source file and associated records.
     *
     * @throws \League\Csv\Exception
     */
    public function migrateRecords(Expedition $expedition): void
    {

        $sourceFile = $this->setSourceFile($expedition);

        $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $sourceFile, 'r');
        $this->awsS3CsvService->createCsvReaderFromStream();
        $this->awsS3CsvService->csv->setHeaderOffset();
        $header = $this->awsS3CsvService->csv->getHeader();
        $records = $this->awsS3CsvService->csv->getRecords($header);

        foreach ($records as $record) {
            $this->geoLocateExport->updateOrCreate(['subject_id' => (int) $record['subject_id']], $record);
        }

        $this->awsS3CsvService->closeBucketStream();
    }

    /**
     * Set the source file path for the expedition.
     *
     * @param  Expedition  $expedition  The expedition instance containing the required data.
     * @return string Returns the file path of the source file.
     */
    private function setSourceFile(Expedition $expedition): string
    {
        return config('zooniverse.directory.'.$expedition->geoLocateDataSource->download->type).'/'.$expedition->id.'.csv';
    }

    /**
     * Sets the CSV file path for the specified expedition ID.
     *
     * @param  int  $expeditionId  The ID of the expedition to generate the CSV file path for.
     */
    public function setCsvFilePath(int $expeditionId): void
    {
        $this->csvFilePath = config('geolocate.dir.export').'/'.$expeditionId.'.csv';
    }

    /**
     * Retrieve the file path of the CSV file.
     *
     * @return string The file path of the CSV file.
     */
    public function getCsvFilePath(): string
    {
        return $this->csvFilePath;
    }

    /**
     * Builds and exports CSV data for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition entity containing data and configurations for exporting.
     *
     * @throws CannotInsertRecord
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function build(Expedition $expedition): void
    {
        $this->awsS3CsvService->csv->writerCreateFromPath(Storage::disk('efs')->path($this->csvFilePath));

        $cursor = $this->geoLocateExport->where('subject_expeditionId', $expedition->id)
            ->options(['allowDiskUse' => true])->timeout(86400)->cursor();

        $first = true;
        foreach ($cursor as $record) {

            $csvData = $this->setDataArray($record, $expedition->geoLocateDataSource->geoLocateForm);

            if (! isset($csvData)) {
                throw new Exception(t('Csv data returned empty while exporting.'));
            }

            if ($first) {
                $this->awsS3CsvService->csv->insertOne(array_keys($csvData));
                $first = false;
            }

            $this->awsS3CsvService->csv->insertOne($csvData);
        }
    }

    /**
     * Converts form fields and record data into an associative array.
     *
     * @param  GeoLocateExport  $record  The GeoLocate export object containing the data to be mapped.
     * @param  GeoLocateForm  $form  The form object that provides the mapping of fields.
     * @return array An associative array where form field keys are mapped to corresponding record values.
     */
    public function setDataArray(GeoLocateExport $record, GeoLocateForm $form): array
    {
        $data = collect($form->fields)->mapWithKeys(function (array $field) use ($record) {
            return [$field['geo'] => $record->{$field['csv']}];
        })->toArray();

        $data['CatalogNumber'] = $record->id;

        return $data;
    }

    /**
     * Moves a CSV file from the local EFS storage to AWS S3 storage.
     *
     * The method checks the existence of the CSV file in the EFS storage, transfers it to S3 storage, verifies the transfer,
     * and deletes the file from the original location in EFS if the transfer is successful.
     * Throws an exception if the file cannot be successfully transferred to S3.
     *
     * @throws Exception If the CSV file cannot be moved to AWS S3 storage.
     */
    public function moveCsvFile(): void
    {
        if (Storage::disk('efs')->exists($this->csvFilePath)) {
            Storage::disk('s3')->writeStream($this->csvFilePath, Storage::disk('efs')->readStream($this->csvFilePath));

            if (! Storage::disk('s3')->exists($this->csvFilePath)) {
                throw new Exception(t('Could not move csv to AWS storage: %s', $this->csvFilePath));
            }

            Storage::disk('efs')->delete($this->csvFilePath);
        }
    }

    /**
     * Creates or updates a download record for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition object for which the download record is created or updated.
     */
    public function createDownload(Expedition $expedition): void
    {
        $values = [
            'expedition_id' => $expedition->id,
            'actor_id' => config('geolocate.actor_id'),
            'file' => $expedition->id.'.csv',
            'type' => 'export',
        ];
        $attributes = [
            'expedition_id' => $expedition->id,
            'actor_id' => config('geolocate.actor_id'),
            'file' => $expedition->id.'.csv',
            'type' => 'export',
        ];

        $this->download->updateOrCreate($attributes, $values);
    }

    /**
     * Updates the pivot table record for the actor associated with the given expedition.
     *
     * @param  Expedition  $expedition  The expedition instance whose actor pivot data is being updated.
     */
    public function updateActorExpeditionPivot(Expedition $expedition): void
    {
        $expedition->actors()->updateExistingPivot(config('geolocate.actor_id'), [
            'state' => ActorExpeditionState::Exported->value,
        ]);
    }

    /**
     * Deletes GeoLocate-related data and dissociates the GeoLocate form from the expedition.
     *
     * @param  Expedition  $expedition  The expedition instance from which GeoLocate data will be removed.
     */
    public function destroyGeoLocate(Expedition $expedition): void
    {
        $this->deleteGeoLocateFile($expedition->id);
        $this->deleteGeoLocateRecords($expedition->id);
        $expedition->geoLocateDataSource->delete();

        $expedition->actors()->updateExistingPivot(config('geolocate.actor_id'), [
            'state' => ActorExpeditionState::NotStarted->value,
        ]);
    }

    /**
     * Deletes a GeoLocate file associated with the specified expedition ID from the storage.
     *
     * @param  int  $expeditionId  The ID of the expedition whose GeoLocate file needs to be deleted.
     */
    public function deleteGeoLocateFile(int $expeditionId): void
    {
        $filePath = config('geolocate.dir.export').'/'.$expeditionId.'.csv';
        if (Storage::disk('s3')->exists($filePath)) {
            Storage::disk('s3')->delete($filePath);
        }
    }

    /**
     * Deletes all GeoLocate records associated with a specific expedition.
     *
     * @param  int  $expeditionId  The ID of the expedition whose GeoLocate records need to be deleted.
     */
    public function deleteGeoLocateRecords(int $expeditionId): void
    {
        $this->geoLocateExport->where('subject_expeditionId', '=', $expeditionId)->get()->each(function ($geoLocate) {
            $geoLocate->delete();
        });
    }
}
