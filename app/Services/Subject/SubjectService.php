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
     * Flag indicating if group operation is 'AND' (true) or 'OR' (false).
     */
    protected bool $groupAnd;

    /**
     * Flag indicating if group operation has been processed.
     */
    protected bool $groupOpProcessed;

    /**
     * Stores the assigned rule data for filtering subjects.
     * Possible values: 'all', 'true', 'false', null
     */
    protected mixed $assignedRuleData = null;

    /**
     * Service class for handling Subject model operations including CRUD operations, grid operations,
     * expedition attachments/detachments, and complex query building.
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
     * @return \App\Models\Subject|bool Returns the updated Subject model on success, false on failure
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
     * Detach subjects from expedition.
     */
    public function detachSubjects(Collection $subjectIds, int $expeditionId): void
    {
        $subjectIds->each(function ($subjectId) use ($expeditionId) {
            $subject = $this->subject->find($subjectId);
            $subject->expedition_ids = collect($subject->expedition_ids)->filter(function ($value) use ($expeditionId) {
                return $value != $expeditionId;
            })->unique()->toArray();

            $subject->save();
        });
    }

    /**
     * Attach subjects to expedition.
     */
    public function attachSubjects(Collection $subjectIds, int $expeditionId): void
    {
        $subjectIds->each(function ($subjectId) use ($expeditionId) {
            $subject = $this->subject->find($subjectId);
            $subject->expedition_ids = collect($subject->expedition_ids)->push($expeditionId)->unique()->toArray();
            $subject->save();
        });
    }

    /**
     * Calculate the number of rows. It's used for paging the result.
     *
     * @param  array  $vars
     *                       page
     *                       limit
     *                       count
     *                       sidx
     *                       sord
     *                       filters
     *                       projectId
     *                       expeditionId
     *
     *  Filters is an array: example: array(array('field'=>'column index/name 1','op'=>'operator','data'=>'searched string column 1'), array('field'=>'column index/name 2','op'=>'operator','data'=>'searched string column 2'))
     *  The 'field' key will contain the 'index' column property if is set, otherwise the 'name' column property.
     *  The 'op' key will contain one of the following operators: '=', '<', '>', '<=', '>=', '<>', '!=','like', 'not like', 'is in', 'is not in'.
     *  when the 'operator' is 'like' the 'data' already contains the '%' character in the appropiate position.
     *  The 'data' key will contain the string searched by the user.
     * @return int Total number of rows
     *
     * @throws \Exception
     */
    public function getGridTotalRowCount(array $vars = []): int
    {
        $count = $this->subject->whereNested(function ($query) use ($vars) {
            $this->buildQuery($query, $vars);
        })->options(['allowDiskUse' => true])->count();

        return (int) $count;
    }

    /**
     * Get the rows data to be shown in the grid.
     *
     * @param  $vars
     *               An array of filters, example: array(array('field'=>'column index/name 1','op'=>'operator','data'=>'searched string column 1'), array('field'=>'column index/name 2','op'=>'operator','data'=>'searched string column 2'))
     *               The 'field' key will contain the 'index' column property if is set, otherwise the 'name' column property.
     *               The 'op' key will contain one of the following operators: '=', '<', '>', '<=', '>=', '<>', '!=','like', 'not like', 'is in', 'is not in'.
     *               when the 'operator' is 'like' the 'data' already contains the '%' character in the appropiate position.
     *               The 'data' key will contain the string searched by the user.
     * @return array
     *               An array of array, each array will have the data of a row.
     *               Example: array(array('row 1 col 1','row 1 col 2'), array('row 2 col 1','row 2 col 2'))
     *
     * $limit, $start, $sidx, $sord, $filters
     *
     * @throws \Exception
     */
    public function getGridRows(array $vars = []): array
    {
        $query = $this->subject->whereNested(function ($query) use ($vars) {
            $this->buildQuery($query, $vars);
        })->options(['allowDiskUse' => true])->take($vars['limit'])->skip($vars['offset']);

        foreach ($vars['orderBy'] as $field => $sort) {
            $query->orderBy($field, $sort);
        }

        $rows = $query->get();

        if (! is_array($rows)) {
            $rows = $rows->toArray();
        }

        $this->setRowCheckbox($rows);

        return $rows;
    }

    /**
     * Return query used to chunk rows for export.
     */
    public function exportGridRows(array $vars): LazyCollection
    {
        $query = $this->subject->with('occurrence')->whereNested(function ($query) use ($vars) {
            $this->buildQuery($query, $vars);
        })->options(['allowDiskUse' => true]);

        foreach ($vars['orderBy'] as $field => $sort) {
            $query->orderBy($field, $sort);
        }

        return $query->orderBy('id', 'desc')->lazy();
    }

    /**
     * Build query for search.
     */
    protected function buildQuery(&$query, $vars): void
    {
        $this->setGroupOp($vars['filters']);

        $this->setWhere($query, 'project_id', '=', $vars['projectId']);

        if (isset($vars['filters']['rules']) && is_array($vars['filters']['rules'])) {
            $rules = $vars['filters']['rules'];
            $query->where(function ($query) use ($rules, $vars) {
                $this->handleRules($query, $rules, $vars);
            });
        }

        $this->setExpeditionWhere($query, $vars);
    }

    /**
     * Set where for expedition ids depending on route.
     */
    protected function setExpeditionWhere(&$query, $vars): void
    {
        // explore: Project Explore (show all)
        // show: Expedition Show page (show only assigned)
        // edit: Expedition edit (show all)
        // create: Expedition create (show not assigned, unless assigned filter is 'all' or 'true')
        if ($vars['route'] === 'explore' || ($vars['route'] === 'edit' && $this->assignedRuleData === 'all')) {
            return;
        } elseif ($vars['route'] === 'show') {
            $this->setWhereIn($query, 'expedition_ids', [$vars['expeditionId']]);
        } elseif ($vars['route'] === 'create') {
            // Only filter for unassigned subjects if the assigned filter is not set to 'all' or 'true'
            if ($this->assignedRuleData !== 'all' && $this->assignedRuleData !== 'true') {
                $this->setWhere($query, 'expedition_ids', 'size', 0);
            }
        }
    }

    /**
     * Handle the passed filters.
     */
    protected function handleRules(&$query, $rules, $vars = []): void
    {
        foreach ($rules as $rule) {
            if ($rule['field'] === 'assigned') {
                $this->assignedRule($query, $rule, $vars);

                continue;
            }

            if ($rule['field'] === 'expedition_ids') {
                $rule['data'] = (int) $rule['data'];
            }

            if ($rule['field'] === 'exported') {
                if ($rule['data'] === 'all') {
                    continue;
                }
                $rule['data'] = $rule['data'] === 'true' ? (bool) true : (bool) false;
            }

            $this->buildWhere($query, $rule);
        }
    }

    /**
     * Build where clause for query.
     * eq: equal
     * ne: not equal
     * bw: begins with
     * bn: does not begin with
     * ew: ends with
     * en: does not end with
     * cn: contains
     * nc: does not contains
     * nu: is null
     * nn: is not null
     * lt: less than
     * le: less or equal
     * gt: greater
     * ge: greater or equal
     * in: is in
     * ni: is not in
     *
     * 'eq', 'ne', 'bw', 'bn', 'ew', 'en', 'cn', 'nc', 'nu', 'nn'
     */
    protected function buildWhere(&$query, $rule): void
    {
        switch ($rule['op']) {
            case 'bw':
                $this->setWhere($query, $rule['field'], 'regexp', '/^'.$rule['data'].'/i');
                break;
            case 'bn':
                $this->setWhere($query, $rule['field'], 'regexp', '/^(?!'.$rule['data'].').+/i');
                break;
            case 'ew':
                $this->setWhere($query, $rule['field'], 'regexp', '/^(?!'.'/'.$rule['data'].'$/i');
                break;
            case 'en':
                $this->setWhere($query, $rule['field'], 'regexp', '/.*(?<!'.$rule['data'].')$/i');
                break;
            case 'cn':
                $this->setWhere($query, $rule['field'], 'like', '%'.$rule['data'].'%');
                break;
            case 'nc':
                $this->setWhere($query, $rule['field'], 'not regexp', '/'.$rule['data'].'/i');
                break;
            case 'nu':
                $this->setWhereNull($query, $rule['field']);
                break;
            case 'nn':
                $this->setWhereNotNull($query, $rule['field']);
                break;
            case 'in':
                $this->setWhereIn($query, $rule['field'], explode(',', $rule['data']));
                break;
            case 'ni':
                $this->setWhereNotIn($query, $rule['field'], explode(',', $rule['data']));
                break;
            case 'eq':
                $this->setWhere($query, $rule['field'], '=', $rule['data']);
                break;
            case 'ne':
                $this->setWhere($query, $rule['field'], '!=', $rule['data']);
                break;
            case 'lt':
                $this->setWhere($query, $rule['field'], '<', $rule['data']);
                break;
            case 'le':
                $this->setWhere($query, $rule['field'], '<=', $rule['data']);
                break;
            case 'gt':
                $this->setWhere($query, $rule['field'], '>', $rule['data']);
                break;
            case 'ge':
                $this->setWhere($query, $rule['field'], '>=', $rule['data']);
                break;
        }
    }

    /**
     * Filter for if subject is assigned to an expedition.
     * data = all, true, false
     */
    protected function assignedRule(&$query, $rule, $vars = []): void
    {
        // Handle empty string as 'all' - no filtering needed
        if ($rule['data'] === '' || $rule['data'] === 'all') {
            $this->assignedRuleData = 'all';

            return;
        }

        $this->assignedRuleData = $rule['data'];

        if ($rule['data'] === 'current' && isset($vars['expeditionId'])) {
            $this->setWhereIn($query, 'expedition_ids', [(int) $vars['expeditionId']]);

            return;
        }

        $this->setWhereForAssigned($query, $rule);
    }

    protected function setWhereForAssigned(&$query, $rule): void
    {
        if ($rule['data'] === 'true') {
            $this->setWhereRaw($query, 'expedition_ids', ['$not' => ['$size' => 0]]);
        } else {
            $this->setWhere($query, 'expedition_ids', 'size', 0);
        }
    }

    public function setOrderBy($orderBy, $sord): array
    {
        $orderByRaw = [];
        if ($orderBy !== null) {
            $orderBys = explode(',', $orderBy);
            foreach ($orderBys as $order) {
                $order = trim($order);
                [$field, $sort] = array_pad(explode(' ', $order, 2), 2, $sord);
                $orderByRaw[trim($field)] = trim($sort);
            }
        }

        return $orderByRaw;
    }

    /**
     * If row has expeditionId, mark as checked
     */
    protected function setRowCheckbox(&$rows): void
    {
        foreach ($rows as &$row) {
            $row['assigned'] = ! empty($row['expedition_ids']) ? 'Yes' : 'No';
        }
    }

    /**
     * Set group operator.
     *
     * @internal param $groupOp
     */
    protected function setGroupOp($filters): void
    {
        $this->groupAnd = true;

        if (isset($filters['groupOp'])) {
            $this->groupAnd = ($filters['groupOp'] === 'AND');
        }
    }

    /**
     * Set groupOp process
     */
    protected function setGroupOpProcessed($bool = false): void
    {
        $this->groupOpProcessed = $bool;
    }

    /**
     * Set where/orWhere clause for query
     */
    protected function setWhere(&$query, $field, $operation, $data): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->where($field, $operation, $data) : $query->orWhere($field, $operation, $data);

        $this->setGroupOpProcessed(true);
    }

    /**
     * Set whereRaw/orWhereRaw for query
     */
    protected function setWhereRaw(&$query, $field, $data): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->whereRaw([$field => $data]) : $query->orWhereRaw([$field => $data]);

        $this->setGroupOpProcessed(true);
    }

    /**
     * Set whereIn/orWhereIn for query
     */
    protected function setWhereIn(&$query, $field, $data): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->whereIn($field, $data) : $query->orWhereIn($field, $data);

        $this->setGroupOpProcessed(true);
    }

    /**
     * Set whereIn/orWhereIn for query
     */
    protected function setWhereNotIn(&$query, $field, $data): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->whereNotIn($field, $data) : $query->orWhereNotIn($field, $data);

        $this->setGroupOpProcessed(true);
    }

    /**
     * Set whereNull/orWhereNull for query
     */
    protected function setWhereNull(&$query, $field): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->where($field, '') : $query->orWhere($field, '');

        $this->setGroupOpProcessed(true);
    }

    /**
     * Set whereNotNull/orWhereNotNull for query
     */
    protected function setWhereNotNull(&$query, $field): void
    {
        ($this->groupAnd || ! $this->groupOpProcessed) ? $query->where($field, '!=', '') : $query->orWhere($field, '!=', '');

        $this->setGroupOpProcessed(true);
    }
}
