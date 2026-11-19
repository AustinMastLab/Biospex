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

namespace App\Services\Subject;

use App\Models\Expedition;
use App\Models\Project;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class SubjectService
{
    /**
     * Subject reads and writes, and attaching subjects to expeditions. The subject grid's query is
     * SubjectGridQuery.
     */
    public function __construct(public Subject $subject) {}

    /**
     * Creates a new Subject record in the database.
     *
     * @param  array  $data  The data to create the subject with
     * @return mixed The created Subject model instance
     */
    public function create(array $data): mixed
    {
        return $this->subject->create($data);
    }

    /**
     * Updates an existing Subject record in the database.
     *
     * @param  array  $data  The data to update the subject with
     * @param  int|string  $resourceId  The ID of the subject to update
     * @return Subject|bool Returns the updated Subject model on success, false on failure
     */
    public function update(array $data, int|string $resourceId): bool|Subject
    {
        if (! $model = $this->subject->find($resourceId)) {
            return false;
        }

        return $model->update($data) ? $model : false;
    }

    /**
     * Finds a Subject record by its ID.
     *
     * @param  string  $id  The ID of the subject to find
     * @return mixed The found Subject model instance or null if not found
     */
    public function find(string $id): mixed
    {
        return $this->subject->find($id);
    }

    /**
     * Retrieves subjects where field values match any value in the given array.
     *
     * @param  string  $field  The field to check against
     * @param  array  $values  Array of values to match against
     * @param  array|string[]  $columns  The columns to retrieve
     * @return mixed Collection of matching Subject models
     */
    public function getWhereIn(string $field, array $values, array $columns = ['*']): mixed
    {
        return $this->subject->whereIn($field, $values)->get($columns);
    }

    /**
     * Finds all subjects associated with a specific expedition ID.
     *
     * @param  int|string  $expeditionId  The expedition ID to search for
     * @param  array|string[]  $attributes  The attributes to retrieve
     * @return array|mixed Collection of Subject models belonging to the expedition
     */
    public function findByExpeditionId($expeditionId, array $attributes = ['*']): mixed
    {
        return $this->subject->where('expedition_ids', $expeditionId)->get($attributes);
    }

    /**
     * Get subjects by expedition id and return using lazycollection.
     */
    public function getSubjectCursorForExport($expeditionId): LazyCollection
    {
        return $this->subject->where('expedition_ids', $expeditionId)->options(['allowDiskUse' => true])
            ->timeout(86400)->cursor();
    }

    /**
     * Return query for processing subjects in ocr.
     */
    public function getSubjectCursorForOcr(Project $project, ?Expedition $expedition = null): LazyCollection
    {
        $query = $this->subject->where('project_id', $project->id);
        $query = $expedition === null ? $query : $query->where('expedition_ids', $expedition->id);

        return $query->where(function ($q) {
            $q->where('ocr', '')
                ->orWhereNull('ocr')
                ->orWhere('ocr', '[OCR produced no text]')
                ->orWhere('ocr', 'regex', '/^\[OCR Failed\]/i')
                ->orWhere('ocr', 'regex', '/^Error/i');
        })->options(['allowDiskUse' => true])->timeout(86400)->cursor();
    }

    /**
     * Get subject cursor for OCR processing.
     */
    public function getSubjectCountForOcr(Project $project, ?Expedition $expedition = null): int
    {
        $query = $this->subject->where('project_id', $project->id);
        $query = $expedition === null ? $query : $query->where('expedition_ids', $expedition->id);

        return $query->where(function ($query) {
            $query->where('ocr', '')
                ->orWhereNull('ocr')
                ->orWhere('ocr', '[OCR produced no text]')
                ->orWhere('ocr', 'regex', '/^\[OCR Failed\]/i')
                ->orWhere('ocr', 'regex', '/^Error/i');
        })->count();
    }

    /**
     * Number of subject ids sent to MongoDB in one bulk update.
     */
    private const SUBJECT_CHUNK_SIZE = 1000;

    /**
     * Detach subjects from expedition.
     *
     * Removes the expedition id from each subject's expedition_ids with one
     * `$pull` update per chunk. Safe to repeat.
     */
    public function detachSubjects(Collection $subjectIds, int $expeditionId): void
    {
        $subjectIds->filter()->unique()->chunk(self::SUBJECT_CHUNK_SIZE)->each(function (Collection $chunk) use ($expeditionId) {
            $this->subject->newQuery()->whereIn('id', $chunk->values()->all())->pull('expedition_ids', $expeditionId);
        });
    }

    /**
     * Attach subjects to expedition.
     *
     * Adds the expedition id to each subject's expedition_ids with one
     * `$addToSet` update per chunk, so repeating it never duplicates the id.
     */
    public function attachSubjects(Collection $subjectIds, int $expeditionId): void
    {
        $subjectIds->filter()->unique()->chunk(self::SUBJECT_CHUNK_SIZE)->each(function (Collection $chunk) use ($expeditionId) {
            $this->subject->newQuery()->whereIn('id', $chunk->values()->all())->push('expedition_ids', $expeditionId, true);
        });
    }

    /**
     * Get the ids (as strings) of subjects assigned to an expedition.
     */
    public function getIdsByExpeditionId(int $expeditionId): Collection
    {
        return $this->subject->newQuery()->where('expedition_ids', $expeditionId)->get(['_id'])
            ->toBase()
            ->map(fn ($subject) => (string) $subject->id);
    }

    /**
     * Count subjects assigned to an expedition.
     */
    public function countByExpeditionId(int $expeditionId): int
    {
        return $this->subject->newQuery()->where('expedition_ids', $expeditionId)->count();
    }
}
