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
use App\Models\Reconcile;
use App\Services\Csv\AwsS3CsvService;
use App\Services\Subject\SubjectService;
use Exception;
use File;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Storage;

/**
 * Class ExpertReconcileService
 */
class ExpertReconcileService
{
    /**
     * @var Repository|Application|mixed
     */
    private mixed $problemRegex;

    /**
     * ExpertReconcileService constructor.
     */
    public function __construct(
        protected Reconcile $reconcile,
        protected SubjectService $subjectService,
        protected AwsS3CsvService $awsS3CsvService
    ) {
        $this->problemRegex = config('zooniverse.reconcile_problem_regex');
    }

    /**
     * Migrate reconcile csv to mongodb using first or create.
     *
     * Transcription fields are encoded to prevent field name errors while saving to db.
     *
     * @throws \League\Csv\Exception|Exception
     */
    public function migrateReconcileCsv(string $expeditionId): void
    {
        $file = config('zooniverse.directory.reconciled').'/'.$expeditionId.'.csv';

        if (! Storage::disk('s3')->exists($file)) {
            $message = t('File does not exist.');
            $method = __METHOD__;
            throw new Exception(\View::make('common.exception', compact('message', 'method', 'file')));
        }

        $rows = $this->getCsvRows($file);

        $rows->reject(function ($row) {
            return $this->validateReconcile($row['subject_id']);
        })->each(function ($row) use (&$create) {
            $newRecord = [];
            foreach ($row as $field => $value) {
                $newField = TranscriptionMapHelper::encodeTranscriptionField($field);
                $newRecord[$newField] = $value;
                $newRecord['subject_problem'] = 0;
                $newRecord['subject_columns'] = '';
            }
            $this->reconcile->create($newRecord);
        });
    }

    /**
     * Get csv rows from file.
     *
     * @throws \League\Csv\Exception
     */
    public function getCsvRows(string $file): Collection
    {
        $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $file, 'r');
        $this->awsS3CsvService->createCsvReaderFromStream();
        $this->awsS3CsvService->csv->setHeaderOffset();

        return collect($this->awsS3CsvService->csv->getRecords($this->awsS3CsvService->csv->getHeader()));
    }

    /**
     * Get pagination results.
     */
    public function getPagination(int $expeditionId): LengthAwarePaginator
    {
        return $this->reconcile->with(['transcriptions'])
            ->where('subject_expeditionId', $expeditionId)
            ->where('subject_problem', 1)
            ->orderBy('subject_id', 'asc')
            ->paginate(1);
    }

    /**
     * Get image url from api or accessURI.
     */
    public function getImageUrl(string $imageName, ?string $location = null): mixed
    {
        return $location !== null ? $location : $this->getAccessUri($imageName);
    }

    /**
     * Get image from accessURI.
     */
    public function getAccessUri($imageName): mixed
    {
        $id = File::name($imageName);
        $subject = $this->subjectService->find($id, ['accessURI']);

        return $subject->accessURI;
    }

    /**
     * Update reconciled record.
     *
     * Unset unneeded variables and decode columns. Use str_place for some columns with spaces.
     */
    public function updateRecord(array $request): mixed
    {
        $id = $request['_id'];
        unset($request['_id'], $request['_method'], $request['_token'], $request['page'], $request['radio']);

        $attributes = [];
        foreach ($request as $key => $value) {
            $attributes[str_replace('--', ' ', $key)] = is_null($value) ? '' : $value;
        }
        $attributes['reviewed'] = 1;

        $model = $this->reconcile->find($id);
        $result = $model->fill($attributes)->save();

        return $result ? $model : false;
    }

    /**
     * Set problem columns in reconcile documents.
     *
     * @throws \League\Csv\Exception|Exception
     */
    public function setReconcileProblems(int $expeditionId): void
    {
        $file = config('zooniverse.directory.explained').'/'.$expeditionId.'.csv';

        if (! Storage::disk('s3')->exists($file)) {
            $message = t('File does not exist.');
            $method = __METHOD__;
            throw new Exception(\View::make('common.exception', compact('message', 'method', 'file')));
        }

        $rows = $this->getCsvRows($file);

        $rows->mapWithKeys(function ($row) {
            $problems = collect($row)->filter(function ($value, $field) {
                return $this->checkForProblem($value, $field);
            });

            return [$row['subject_id'] => $problems];
        })->reject(function ($problem) {
            return $problem->isEmpty();
        })->mapWithKeys(function ($problem, $subjectId) {
            $string = $problem->keys()->map(function ($key) {
                $trimmedKey = trim(str_replace('Explanation', '', $key));

                return TranscriptionMapHelper::encodeTranscriptionField($trimmedKey);
            })->flatten()->join('|');

            return [$subjectId => $string];
        })->each(function ($columns, $subjectId) {
            $reconcile = $this->reconcile->where('subject_id', $subjectId)->first();
            if ($reconcile === null) {
                return;
            }
            $reconcile->subject_problem = 1;
            $reconcile->subject_columns = $columns;
            $reconcile->save();
        });
    }

    /**
     * Check columns for problems.
     *
     * Regex match = /No (?:select|text) match on|Only 1 transcript in|There was 1 number in/i
     */
    private function checkForProblem(string $value, string $field): bool
    {
        return str_contains($field, 'Explanation') && preg_match($this->problemRegex, $value);
    }

    /**
     * Whether the subject's reconcile record is already stored, to prevent duplicates.
     *
     * Queried directly: subject ids are stored as integers, and since Laravel 13.43 the unique validation rule
     * compares as strings, so it never found an existing one.
     */
    private function validateReconcile($subject_id): bool
    {
        return $this->reconcile->where('subject_id', (int) $subject_id)->exists();
    }
}
