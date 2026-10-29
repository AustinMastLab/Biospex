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

use App\Models\ActorExpedition;
use App\Models\Download;
use App\Models\GeoLocateExport;
use App\Services\Csv\AwsS3CsvService;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\SyntaxError;

/**
 * Constructor method for GeoLocateResultCsvService.
 *
 * @param  AwsS3CsvService  $awsS3CsvService  Service for handling AWS S3 interactions and CSV operations.
 * @param  GeoLocateExport  $geoLocateExport  Model for interacting with GeoLocateExport records.
 * @param  Download  $download  Model for managing download records.
 */
class GeoLocateResultCsvService
{
    /**
     * Refers to the file path or location from which data or a file is being sourced or read.
     */
    public string $sourceFile;

    /**
     * Represents the target file path or location where data or a file will be saved.
     */
    protected string $destinationFile;

    /**
     * Defines the header structure of a GeoCSV file, typically containing column names or field identifiers.
     */
    protected array $geoCsvHeader;

    /**
     * Initializes the class with required service dependencies.
     *
     * @param  AwsS3CsvService  $awsS3CsvService  The service for handling AWS S3 CSV operations.
     * @param  GeoLocateExport  $geoLocateExport  The service responsible for geolocation export functionality.
     * @param  Download  $download  The service for handling file download operations.
     * @return void
     */
    public function __construct(
        protected AwsS3CsvService $awsS3CsvService,
        protected GeoLocateExport $geoLocateExport,
        protected Download $download
    ) {}

    /**
     * Sets the source file path based on the actor expedition provided.
     *
     * @param  ActorExpedition  $actorExpedition  The ActorExpedition instance containing expedition details.
     */
    public function setSourceFile(ActorExpedition $actorExpedition): void
    {
        $this->sourceFile = config('geolocate.dir.csv').'/'.$actorExpedition->expedition->geoLocateCsvDownload->file;
    }

    /**
     * Sets the destination file path based on the actor expedition provided.
     *
     * @param  ActorExpedition  $actorExpedition  The ActorExpedition instance containing expedition details.
     */
    public function setDestinationFile(ActorExpedition $actorExpedition): void
    {
        $this->destinationFile = config('geolocate.dir.geo-reconciled').'/'.$actorExpedition->expedition_id.'.csv';
    }

    /**
     * Processes the download of a CSV file from an S3 bucket and cleans its header based on the provided fields.
     *
     * @param  array  $fields  The GeoLocate Form fields used to clean and adjust the header of the CSV file.
     *
     * @throws Exception
     * @throws SyntaxError
     */
    public function processCsvDownload(array $fields): void
    {
        $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $this->sourceFile, 'r');
        $this->awsS3CsvService->createCsvReaderFromStream();
        $this->awsS3CsvService->csv->setHeaderOffset();
        $header = $this->awsS3CsvService->csv->getHeader();
        $this->geoCsvHeader = $this->filterGeoCsvHeader($header, $fields);
        $this->processRecords();
        $this->awsS3CsvService->closeBucketStream();
    }

    /**
     * Creates or updates a Geo Reconciled download file for a specified expedition ID.
     *
     * @param  int  $expeditionId  The ID of the expedition for which the Geo Reconciled download is being created or updated.
     *
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function createUpdateGeoReconciledDownload(int $expeditionId): void
    {
        $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $this->destinationFile, 'w');
        $this->awsS3CsvService->createCsvWriterFromStream();

        $records = fn () => $this->geoLocateExport->where('subject_expeditionId', $expeditionId)->lazy()
            ->map(function (GeoLocateExport $record) {
                unset($record->id, $record->updated_at, $record->created_at);

                return $record->toArray();
            });

        // First pass: the header is every record's fields plus the GeoLocate columns,
        // so a field missing from the first record still gets its own column.
        $keys = [];
        foreach ($records() as $record) {
            $keys += array_fill_keys(array_keys($record), null);
        }

        if ($keys === []) {
            $this->awsS3CsvService->closeBucketStream();

            return;
        }

        $recordHeader = array_fill_keys($this->setMergedHeader(array_keys($keys)), null);
        $this->awsS3CsvService->csv->insertOne(array_keys($recordHeader));

        foreach ($records() as $record) {
            $this->awsS3CsvService->csv->insertOne(array_replace($recordHeader, array_intersect_key($record, $recordHeader)));
        }

        $this->awsS3CsvService->closeBucketStream();
    }

    /**
     * Creates a merged header array by combining keys from the record and an existing geoCsvHeader,
     * then removing the 'CatalogNumber' key.
     *
     * @param  GeoLocateExport  $record  The GeoLocateExport instance containing record data for processing.
     * @return array The resulting array after merging and removing specific keys.
     */
    private function setMergedHeader(array $recordKeys): array
    {
        return array_values(array_unique(array_diff(array_merge($recordKeys, $this->geoCsvHeader), ['CatalogNumber'])));
    }

    /**
     * Cleans the provided CSV header by removing fields that match the given set of fields.
     * The method filters out any header elements that exist in the flattened fields array.
     *
     * @param  array  $csvHeader  The original header of the CSV file as an array.
     * @param  array  $fields  The fields to be removed from the CSV header.
     * @return array The cleaned CSV header with specified fields removed.
     */
    public function filterGeoCsvHeader(array $csvHeader, array $fields): array
    {
        $flatFields = collect($fields)->flatten()->toArray();

        return collect($csvHeader)->filter(function ($item) use ($flatFields) {
            return ! in_array($item, $flatFields);
        })->toArray();
    }

    /**
     * Processes the records retrieved from the CSV, finds the corresponding geo-location export record based on the catalog number,
     * updates the found record with the remaining data, and removes the catalog number from the data array.
     *
     * @throws Exception
     */
    public function processRecords(): void
    {
        $records = $this->awsS3CsvService->csv->getRecords();
        foreach ($records as $record) {
            $filteredRecord = $this->getFilteredRecord($record);
            $geoLocateExport = $this->geoLocateExport->find($filteredRecord['CatalogNumber']);
            unset($filteredRecord['CatalogNumber']);
            $geoLocateExport->update($filteredRecord);
        }
    }

    /**
     * Creates or updates a download record associated with the specified expedition ID.
     *
     * @param  int  $expeditionId  The identifier of the expedition for which the download record is being created or updated.
     */
    public function createOrUpdateDownload(int $expeditionId): void
    {
        $this->download->updateOrCreate([
            'expedition_id' => $expeditionId,
            'actor_id' => config('geolocate.actor_id'),
            'file' => $expeditionId.'.csv',
            'type' => 'geo-reconciled',
        ], [
            'expedition_id' => $expeditionId,
            'actor_id' => config('geolocate.actor_id'),
            'file' => $expeditionId.'.csv',
            'type' => 'geo-reconciled',
        ]);
    }

    /**
     * Filters the given record array to include only the keys specified in the geoCsvHeader property.
     *
     * @param  array  $record  The input array to be filtered.
     * @return array The filtered array containing only allowed keys.
     */
    private function getFilteredRecord(array $record): array
    {
        return array_filter($record, fn ($key) => in_array($key, $this->geoCsvHeader), ARRAY_FILTER_USE_KEY);
    }
}
