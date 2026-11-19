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

namespace App\Services\Subject;

use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Eloquent\Builder;

/**
 * The subject grid's MongoDB query, shared by the grid (App\Livewire\Admin\SubjectGrid) and its CSV export
 * (GridExportCsvJob).
 *
 * Criteria:
 * - projectId (int), mode (explore|show|create|edit), expeditionId (?int)
 * - assigned: all | true | false | current (the expedition's subjects) | chosen | unchosen (against chosenIds)
 * - exported: all | true | false
 * - filters: [column => text]. Text matches anywhere, ignoring case. "florida or georgia" (or "florida | georgia")
 *   matches either; "escambia and beach" needs both, in any order. "and" binds tighter than "or", so
 *   "escambia and beach or santa rosa" is (escambia and beach) or santa rosa. expedition_ids matches whole ids.
 * - chosenIds: subject ids chosen in the grid, for the chosen and unchosen filters
 *
 * @phpstan-type Criteria array{projectId: int, mode?: string, expeditionId?: ?int, assigned?: string, exported?: string, filters?: array<string, string>, chosenIds?: array<int, string>}
 */
class SubjectGridQuery
{
    public function __construct(protected Subject $subject) {}

    /**
     * How many subjects match.
     *
     * @param  Criteria  $criteria
     */
    public function count(array $criteria): int
    {
        return (int) $this->query($criteria)->count();
    }

    /**
     * One page of matching subjects, as arrays, with "assigned" set to Yes or No.
     *
     * @param  Criteria  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function page(array $criteria, string $sortField, string $sortDirection, int $limit, int $offset): array
    {
        return $this->sorted($this->query($criteria), $sortField, $sortDirection)
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function (Subject $subject) {
                $row = $subject->toArray();
                $row['assigned'] = empty($row['expedition_ids']) ? 'No' : 'Yes';

                return $row;
            })
            ->all();
    }

    /**
     * The ids of every matching subject, for "Select all".
     *
     * @param  Criteria  $criteria
     * @return Collection<int, string>
     */
    public function ids(array $criteria): Collection
    {
        return $this->query($criteria)->get(['_id'])->toBase()->map(fn (Subject $subject) => (string) $subject->id);
    }

    /**
     * Every matching subject, read in batches, for the CSV export.
     *
     * @param  Criteria  $criteria
     * @return LazyCollection<int, Subject>
     */
    public function cursor(array $criteria, string $sortField = '_id', string $sortDirection = 'desc'): LazyCollection
    {
        return $this->sorted($this->query($criteria), $sortField, $sortDirection)->cursor();
    }

    /**
     * @param  Criteria  $criteria
     */
    public function query(array $criteria): Builder
    {
        $mode = $criteria['mode'] ?? 'explore';
        $expeditionId = $criteria['expeditionId'] ?? null;
        $query = $this->subject->newQuery()
            ->where('project_id', (int) $criteria['projectId'])
            ->options(['allowDiskUse' => true]);

        if ($mode === 'show' && $expeditionId !== null) {
            $query->where('expedition_ids', (int) $expeditionId);
        }

        $this->whereAssigned($query, $criteria['assigned'] ?? 'all', $expeditionId, $criteria['chosenIds'] ?? []);

        if (in_array($criteria['exported'] ?? 'all', ['true', 'false'], true)) {
            $query->where('exported', $criteria['exported'] === 'true');
        }

        foreach ($criteria['filters'] ?? [] as $column => $text) {
            $this->whereText($query, (string) $column, (string) $text);
        }

        return $query;
    }

    /**
     * A filter's search terms: alternatives separated by "or" (or "|"), each a list of terms that must all match,
     * separated by "and". "escambia and beach or santa rosa" is [["escambia", "beach"], ["santa rosa"]].
     *
     * @return array<int, array<int, string>>
     */
    public function terms(string $text): array
    {
        $alternatives = [];

        foreach (preg_split('/\s+or\s+|\|/i', trim($text)) ?: [] as $alternative) {
            $terms = array_values(array_filter(array_map('trim', preg_split('/\s+and\s+/i', $alternative) ?: [])));

            if ($terms !== []) {
                $alternatives[] = $terms;
            }
        }

        return $alternatives;
    }

    /**
     * @param  array<int, string>  $chosenIds
     */
    protected function whereAssigned(Builder $query, string $assigned, ?int $expeditionId, array $chosenIds): void
    {
        match ($assigned) {
            'true' => $query->where('expedition_ids.0', 'exists', true),
            'false' => $query->where('expedition_ids.0', 'exists', false),
            'current' => $expeditionId !== null ? $query->where('expedition_ids', $expeditionId) : null,
            'chosen' => $query->whereIn('_id', $this->objectIds($chosenIds)),
            'unchosen' => $query->whereNotIn('_id', $this->objectIds($chosenIds)),
            default => null,
        };
    }

    protected function whereText(Builder $query, string $column, string $text): void
    {
        $terms = $this->terms($text);

        if ($terms === [] || in_array($column, ['assigned', 'exported', '_id', 'id'], true)) {
            return;
        }

        if ($column === 'expedition_ids') {
            $query->whereIn('expedition_ids', array_map('intval', array_filter(array_merge(...$terms), 'is_numeric')));

            return;
        }

        // Any alternative may match; within one, every term must. Terms are matched literally: characters such
        // as ( * . would otherwise change the pattern.
        $query->where(function (Builder $query) use ($column, $terms) {
            foreach ($terms as $alternative) {
                $query->orWhere(function (Builder $query) use ($column, $alternative) {
                    foreach ($alternative as $term) {
                        $query->where($column, 'regexp', '/'.preg_quote($term, '/').'/i');
                    }
                });
            }
        });
    }

    protected function sorted(Builder $query, string $sortField, string $sortDirection): Builder
    {
        $direction = $sortDirection === 'asc' ? 'asc' : 'desc';

        if ($sortField !== '_id') {
            $query->orderBy($sortField, $direction);
        }

        // _id last keeps pages stable when many subjects share a value.
        return $query->orderBy('_id', $sortField === '_id' ? $direction : 'desc');
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, ObjectId>
     */
    protected function objectIds(array $ids): array
    {
        return array_values(array_map(fn (string $id) => new ObjectId($id), array_filter($ids, fn ($id) => preg_match('/^[0-9a-f]{24}$/i', (string) $id) === 1)));
    }
}
