<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

use App\Services\Csv\Csv;

beforeEach(function () {
    $this->path = tempnam(sys_get_temp_dir(), 'csv');
    // A JSON-encoded quote, as in the occurrence column of grid exports.
    $this->row = ['subject-1', '{"locality":"80\"22\"38\"W."}', 'plain'];
});

afterEach(function () {
    @unlink($this->path);
});

it('writes csv that standard RFC 4180 readers parse back unchanged', function () {
    $csv = new Csv;
    $csv->writerCreateFromPath($this->path);
    $csv->insertOne(['_id', 'occurrence', 'ocr']);
    $csv->insertOne($this->row);
    unset($csv);

    $file = fopen($this->path, 'r');
    fgetcsv($file, null, ',', '"', '');

    expect(fgetcsv($file, null, ',', '"', ''))->toBe($this->row);
});

it('keeps RFC 4180 escaping when setEscape is called without an argument', function () {
    $file = fopen($this->path, 'w');
    fputcsv($file, ['_id', 'occurrence', 'ocr'], ',', '"', '');
    fputcsv($file, $this->row, ',', '"', '');
    fclose($file);

    $csv = new Csv;
    $csv->readerCreateFromPath($this->path);
    $csv->setEscape();
    $csv->setHeaderOffset();

    expect(array_values(iterator_to_array($csv->getRecords())[1]))->toBe($this->row);
});

it('reads standard RFC 4180 csv containing backslash quotes', function () {
    $file = fopen($this->path, 'w');
    fputcsv($file, ['_id', 'occurrence', 'ocr'], ',', '"', '');
    fputcsv($file, $this->row, ',', '"', '');
    fclose($file);

    $csv = new Csv;
    $csv->readerCreateFromPath($this->path);
    $csv->setHeaderOffset();

    expect(array_values(iterator_to_array($csv->getRecords())[1]))->toBe($this->row);
});
