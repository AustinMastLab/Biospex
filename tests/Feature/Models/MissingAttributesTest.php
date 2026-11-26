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

use App\Models\ExportQueue;
use App\Models\ExportQueueFile;
use App\Models\PanoptesTranscription;
use App\Models\Project;
use App\Models\Subject;
use App\Services\Process\MapZooniverseCsvColumnsService;
use Illuminate\Database\Eloquent\MissingAttributeException;

afterEach(function () {
    PanoptesTranscription::truncate();
    Subject::truncate();
});

it('reads a field a MongoDB document does not have as null', function () {
    PanoptesTranscription::create(['classification_id' => 1, 'user_name' => 'volunteer']);

    expect(PanoptesTranscription::sole()->Country)->toBeNull();
});

it('still treats a column left out of a MySQL query as an error', function () {
    $project = Project::factory()->create();

    expect(fn () => Project::query()->select('id')->find($project->id)->title)
        ->toThrow(MissingAttributeException::class);
});

it('exports an empty cell for a subject without an image id', function () {
    Subject::create(['project_id' => 1, 'occurrence' => ['scientificName' => 'Quercus alba']]);
    $subject = Subject::sole();
    $queue = ExportQueue::factory()->create()->load('expedition');
    $file = ExportQueueFile::factory()->create(['queue_id' => $queue->id, 'subject_id' => (string) $subject->_id]);

    $row = app(MapZooniverseCsvColumnsService::class)->mapColumns($file, $queue);

    expect($row['#imageId'])->toBe('')
        ->and($row['scientificName'])->toBe('Quercus alba');
});
