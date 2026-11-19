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

namespace App\Services\Grid;

use App\Models\SubjectGridColumn;
use App\Services\Project\HeaderService;

/**
 * The subject grid's columns for a project, from its import header, and each user's chosen columns.
 */
class SubjectGridColumns
{
    /**
     * Columns every project has, before and after the header's image fields.
     */
    private const array LEADING = ['assigned', 'exported', 'expedition_ids', 'imageId'];

    private const array TRAILING = ['ocr'];

    public function __construct(protected HeaderService $headerService) {}

    /**
     * Every column the project's subjects can show, in their default order: subject fields, then occurrence fields
     * (keys `occurrence.<field>`).
     *
     * @return array<int, string>
     */
    public function all(int $projectId): array
    {
        $header = $this->headerService->getFirst('project_id', $projectId)?->header ?? [];
        $imageFields = is_array($header['image'] ?? null) ? $header['image'] : ['accessURI'];
        $occurrenceFields = is_array($header['occurrence'] ?? null) ? $header['occurrence'] : [];

        $columns = [
            ...self::LEADING,
            ...$imageFields,
            ...self::TRAILING,
            ...array_map(fn (string $field) => 'occurrence.'.$field, $occurrenceFields),
        ];

        return array_values(array_unique($columns));
    }

    /**
     * The columns shown until a user chooses their own.
     *
     * @return array<int, string>
     */
    public function defaults(int $projectId): array
    {
        $visible = ['assigned', ...config('config.defaultGridVisible')];

        return array_values(array_filter($this->all($projectId), fn (string $column) => in_array($column, $visible, true)));
    }

    /**
     * The user's chosen columns, in their order, keeping only columns the project still has.
     *
     * @return array<int, string>
     */
    public function chosen(int $userId, int $projectId): array
    {
        $saved = SubjectGridColumn::where('user_id', $userId)->where('project_id', $projectId)->value('columns');

        if (! is_array($saved)) {
            return $this->defaults($projectId);
        }

        $chosen = array_values(array_intersect($saved, $this->all($projectId)));

        return $chosen === [] ? $this->defaults($projectId) : $chosen;
    }

    /**
     * Save the user's columns, in order, keeping only columns the project has.
     *
     * @param  array<int, string>  $columns
     * @return array<int, string> The saved columns.
     */
    public function save(int $userId, int $projectId, array $columns): array
    {
        $columns = array_values(array_unique(array_intersect($columns, $this->all($projectId))));

        if ($columns === []) {
            return $this->reset($userId, $projectId);
        }

        SubjectGridColumn::updateOrCreate(
            ['user_id' => $userId, 'project_id' => $projectId],
            ['columns' => $columns]
        );

        return $columns;
    }

    /**
     * Forget the user's columns for the project and return the defaults.
     *
     * @return array<int, string>
     */
    public function reset(int $userId, int $projectId): array
    {
        SubjectGridColumn::where('user_id', $userId)->where('project_id', $projectId)->delete();

        return $this->defaults($projectId);
    }
}
