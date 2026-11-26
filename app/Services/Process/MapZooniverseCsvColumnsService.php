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

use App\Models\ExportQueue;
use App\Models\ExportQueueFile;
use App\Models\Subject;
use App\Services\Subject\SubjectService;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

class MapZooniverseCsvColumnsService
{
    /**
     * @var Repository|Application|mixed
     */
    private mixed $zooniverseCsvMap;

    public function __construct(protected SubjectService $subjectService)
    {
        $this->zooniverseCsvMap = config('zooniverse.csv_map');
    }

    /**
     * Map zooniverse csvExport values.
     */
    public function mapColumns(ExportQueueFile $file, ExportQueue $queue): array
    {
        $subject = $this->subjectService->find($file->subject_id);

        $csvArray = [];
        $presetValues = ['#expeditionId', '#expeditionTitle', 'imageName'];

        foreach ($this->zooniverseCsvMap as $key => $item) {
            if (in_array($key, $presetValues)) {
                $this->setPresetValues($csvArray, $key, $file, $queue);

                continue;
            }

            // If subject not found, add error column and message
            if ($subject === null) {
                $csvArray['error'] = 'Could not retrieve subject '.$file->subject_id.' from database for export';

                continue;
            }

            // If item is not array, direct translation
            if (! is_array($item)) {
                // A subject without the field (e.g. no imageId) gets an empty cell.
                $csvArray[$key] = $item === '' ? '' : ($subject->{$item} ?? '');

                continue;
            }

            $csvArray[$key] = '';
            foreach ($item as $doc => $value) {
                is_array($value) ? $this->setArrayValues($csvArray, $key, $doc, $value, $subject) : $this->setValues($csvArray, $key, $doc, $value, $subject);
            }
        }

        $subject->exported = true;
        $subject->save();

        return $csvArray;
    }

    /**
     * Set preset values needing special attention.
     *
     * @return void
     */
    private function setPresetValues(&$csvArray, $key, ExportQueueFile $file, ExportQueue $queue)
    {
        if (strcasecmp($key, '#expeditionId') == 0) {
            $csvArray[$key] = $queue->expedition->id;

            return;
        }

        if (strcasecmp($key, '#expeditionTitle') == 0) {
            $csvArray[$key] = $queue->expedition->title;

            return;
        }

        if (strcasecmp($key, 'imageName') == 0) {
            $csvArray[$key] = $file->subject_id.'.jpg';
        }
    }

    /**
     * Set values of document items if array.
     *
     * @return void
     */
    private function setArrayValues(array &$csvArray, string $key, string $doc, array $array, Subject $subject)
    {
        foreach ($array as $value) {
            if (isset($subject->{$doc}[$value])) {
                $csvArray[$key] = $subject->{$doc}[$value];
            }
        }
    }

    /**
     * Set values of document if value exists. Set special links.
     *
     * @return void
     */
    private function setValues(array &$csvArray, string $key, string $doc, string $value, Subject $subject)
    {
        $links = ['eol', 'mol', 'idigbio'];
        if (isset($subject->{$doc}[$value])) {
            if (in_array($key, $links)) {
                $csvArray[$key] = str_replace('SCIENTIFIC_NAME', rawurlencode($subject->{$doc}[$value]), config('zooniverse.search_urls.'.$key));

                return;
            }

            $csvArray[$key] = $subject->{$doc}[$value];
        }
    }
}
