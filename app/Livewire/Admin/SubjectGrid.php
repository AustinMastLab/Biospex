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

namespace App\Livewire\Admin;

use App\Jobs\GridExportCsvJob;
use App\Models\Expedition;
use App\Models\Project;
use App\Services\Grid\SubjectGridColumns;
use App\Services\Grid\SubjectSelection;
use App\Services\Subject\SubjectGridQuery;
use App\Services\Subject\SubjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The subject grid: a project's subjects with a filter per column, sorting, paging, chosen columns and, on the
 * expedition forms, subject selection kept on the server (App\Services\Grid\SubjectSelection).
 *
 * Modes match the pages: explore (all of a project's subjects), show (an expedition's subjects), create and edit
 * (choosing an expedition's subjects).
 */
class SubjectGrid extends Component
{
    use WithPagination;

    public const string PAGE_NAME = 'subjectsPage';

    /** @var array<int, int> */
    public const array PER_PAGE_OPTIONS = [25, 50, 100, 500, 1000];

    #[Locked]
    public int $projectId;

    #[Locked]
    public ?int $expeditionId = null;

    #[Locked]
    public string $mode = 'explore';

    #[Locked]
    public bool $selectable = false;

    /** Whether the expedition has started, so its subjects can no longer change. */
    #[Locked]
    public bool $started = false;

    #[Locked]
    public ?string $selectionToken = null;

    /** @var array<int, string> Shown columns, in order. */
    public array $columns = [];

    /** @var array<string, string> Filter text per column, keyed "c" plus the column's position in the project's columns (keys can't hold the dots in occurrence columns). */
    public array $filters = [];

    public string $assigned = 'all';

    public string $exported = 'all';

    public string $sortField = '_id';

    public string $sortDirection = 'desc';

    public int $perPage = 25;

    public string $columnSearch = '';

    /** The matching count and the filters it was counted for, so paging and sorting don't count again. */
    #[Locked]
    public ?int $total = null;

    #[Locked]
    public string $totalFor = '';

    /** A message about choosing, for example when the limit is reached. */
    public string $notice = '';

    protected SubjectGridQuery $gridQuery;

    protected SubjectGridColumns $gridColumns;

    protected SubjectSelection $selection;

    public function boot(SubjectGridQuery $gridQuery, SubjectGridColumns $gridColumns, SubjectSelection $selection): void
    {
        $this->gridQuery = $gridQuery;
        $this->gridColumns = $gridColumns;
        $this->selection = $selection;
    }

    public function mount(Project $project, string $mode = 'explore', ?Expedition $expedition = null, bool $selectable = false, bool $started = false): void
    {
        $this->projectId = $project->id;
        $this->expeditionId = $expedition?->id;
        $this->mode = in_array($mode, ['explore', 'show', 'create', 'edit'], true) ? $mode : 'explore';
        $this->started = $started;
        $this->selectable = $selectable && ! $started && in_array($this->mode, ['create', 'edit'], true);
        $this->assigned = $this->mode === 'create' ? 'false' : 'all';
        $this->columns = $this->gridColumns->chosen(Auth::id(), $this->projectId);

        if ($this->selectable) {
            $current = $this->mode === 'edit' && $expedition !== null
                ? app(SubjectService::class)->getIdsByExpeditionId($expedition->id)->all()
                : [];
            $this->selectionToken = $this->selection->start(Auth::id(), $current);
        }
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filters') || in_array($property, ['assigned', 'exported', 'perPage'], true)) {
            if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
                $this->perPage = self::PER_PAGE_OPTIONS[0];
            }

            if (! in_array($this->assigned, $this->assignedOptions(), true)) {
                $this->assigned = 'all';
            }

            $this->notice = '';
            $this->resetPage(self::PAGE_NAME);
        }
    }

    /**
     * Sort by a column, or reverse the order when it is already the sort column.
     */
    public function sortBy(string $column): void
    {
        if ($column === 'assigned' || ! in_array($column, $this->allColumns(), true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $column;
        $this->resetPage(self::PAGE_NAME);
    }

    /**
     * Show or hide a column. A column shown again goes to the end.
     */
    public function toggleColumn(string $column): void
    {
        $columns = in_array($column, $this->columns, true)
            ? array_values(array_diff($this->columns, [$column]))
            : [...$this->columns, $column];

        $this->columns = $this->gridColumns->save(Auth::id(), $this->projectId, $columns);
    }

    /**
     * Move a shown column to a new position (wire:sort in the column chooser).
     */
    public function moveColumn(string $column, int $position): void
    {
        if (! in_array($column, $this->columns, true)) {
            return;
        }

        $columns = array_values(array_diff($this->columns, [$column]));
        array_splice($columns, max(0, min($position, count($columns))), 0, [$column]);

        $this->columns = $this->gridColumns->save(Auth::id(), $this->projectId, $columns);
    }

    public function resetColumns(): void
    {
        $this->columns = $this->gridColumns->reset(Auth::id(), $this->projectId);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->assigned = $this->mode === 'create' ? 'false' : 'all';
        $this->exported = 'all';
        $this->notice = '';
        $this->resetPage(self::PAGE_NAME);
    }

    /**
     * Choose or unchoose one subject.
     */
    public function toggleSubject(string $subjectId, bool $chosen): void
    {
        $this->changeSelection([$subjectId], $chosen);
    }

    /**
     * Choose or unchoose every subject on the current page.
     */
    public function togglePage(bool $chosen): void
    {
        $this->changeSelection(collect($this->rows()->items())->map(fn (array $row) => $this->rowId($row))->all(), $chosen);
    }

    /**
     * Select every subject that matches the filters, on every page. Not offered when more subjects match than an
     * expedition can hold.
     */
    public function selectAllMatching(): void
    {
        if (($this->total ?? 0) > $this->maxCount()) {
            return;
        }

        $this->changeSelection($this->gridQuery->ids($this->criteria())->all(), true);
    }

    /**
     * Unchoose every subject.
     */
    public function clearSelection(): void
    {
        if ($this->selectable) {
            $this->selection->clear(Auth::id(), $this->selectionToken);
            $this->notice = '';
            $this->forgetTotal();
        }
    }

    /**
     * Queue the CSV export of the filtered subjects; the user is emailed a link.
     */
    public function export(): void
    {
        GridExportCsvJob::dispatch(Auth::user(), [
            'projectId' => $this->projectId,
            'criteria' => $this->criteria(),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);

        $this->js("window.notify('check-circle', ".json_encode(t('Export submitted. You will receive an email when the export is complete.')).", 'success')");
    }

    /**
     * Every column the project's subjects can show.
     *
     * @return array<int, string>
     */
    public function allColumns(): array
    {
        return $this->gridColumns->all($this->projectId);
    }

    /**
     * The Assigned dropdown's values for this page.
     *
     * @return array<int, string>
     */
    public function assignedOptions(): array
    {
        return [
            'all', 'true', 'false',
            ...($this->mode === 'edit' ? ['current'] : []),
            ...($this->selectable ? ['chosen', 'unchosen'] : []),
        ];
    }

    /**
     * A subject row's id.
     *
     * @param  array<string, mixed>  $row
     */
    public function rowId(array $row): string
    {
        return (string) ($row['id'] ?? $row['_id'] ?? '');
    }

    /**
     * A subject's value for a column, as text.
     *
     * @param  array<string, mixed>  $row
     */
    public function cellValue(array $row, string $column): string
    {
        $value = $column === 'expedition_ids' ? implode(', ', (array) ($row['expedition_ids'] ?? [])) : Arr::get($row, $column);

        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Add or remove subjects from the selection, keeping it within an expedition's limit.
     *
     * @param  array<int, string>  $subjectIds
     */
    protected function changeSelection(array $subjectIds, bool $chosen): void
    {
        if (! $this->selectable) {
            return;
        }

        $this->notice = '';
        $maximum = $this->maxCount();

        if ($chosen) {
            $current = $this->selection->ids(Auth::id(), $this->selectionToken);
            $new = array_values(array_diff($subjectIds, $current));
            $room = max(0, $maximum - count($current));

            if (count($new) > $room) {
                $this->notice = $room === 0
                    ? t('An Expedition can have at most %s subjects, so no more can be selected.', number_format($maximum))
                    : t('Only %s more subjects can be selected (an Expedition can have at most %s), so %s were selected.', number_format($room), number_format($maximum), number_format($room));
                $subjectIds = array_slice($new, 0, $room);
            }
        }

        $this->selection->change(Auth::id(), $this->selectionToken, $subjectIds, $chosen);

        if (in_array($this->assigned, ['chosen', 'unchosen'], true)) {
            $this->forgetTotal();
        }
    }

    /**
     * The grid's current filters as SubjectGridQuery criteria.
     *
     * @return array<string, mixed>
     */
    protected function criteria(): array
    {
        $allColumns = $this->allColumns();
        $filters = [];

        foreach ($this->filters as $key => $text) {
            $column = $allColumns[(int) ltrim((string) $key, 'c')] ?? null;

            if ($column !== null && trim((string) $text) !== '') {
                $filters[$column] = (string) $text;
            }
        }

        return [
            'projectId' => $this->projectId,
            'mode' => $this->mode,
            'expeditionId' => $this->expeditionId,
            'assigned' => $this->assigned,
            'exported' => $this->exported,
            'filters' => $filters,
            'chosenIds' => in_array($this->assigned, ['chosen', 'unchosen'], true)
                ? $this->selection->ids(Auth::id(), $this->selectionToken)
                : [],
        ];
    }

    protected function rows(): LengthAwarePaginator
    {
        $criteria = $this->criteria();
        $countKey = md5(json_encode(Arr::except($criteria, 'chosenIds')));

        // Paging and sorting don't change the count; only a change of filters (or of the selection, when
        // filtering by it) counts again.
        if ($this->total === null || $this->totalFor !== $countKey) {
            $this->total = $this->gridQuery->count($criteria);
            $this->totalFor = $countKey;
        }

        $lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $page = min(max(1, (int) $this->getPage(self::PAGE_NAME)), $lastPage);

        $rows = $this->total === 0 ? [] : $this->gridQuery->page(
            $criteria, $this->sortField, $this->sortDirection, $this->perPage, ($page - 1) * $this->perPage
        );

        return new LengthAwarePaginator($rows, $this->total, $this->perPage, $page, ['pageName' => self::PAGE_NAME]);
    }

    protected function forgetTotal(): void
    {
        $this->total = null;
    }

    protected function maxCount(): int
    {
        return (int) config('config.expedition_size');
    }

    public function render(): View
    {
        $rows = $this->rows();
        $chosenIds = $this->selectable ? $this->selection->ids(Auth::id(), $this->selectionToken) : [];
        $pageIds = collect($rows->items())->map(fn (array $row) => $this->rowId($row))->all();

        return view('livewire.admin.subject-grid', [
            'rows' => $rows,
            'allColumns' => $this->allColumns(),
            'chosenOnPage' => array_values(array_intersect($pageIds, $chosenIds)),
            'pageIds' => $pageIds,
            'chosenCount' => count($chosenIds),
            'maxCount' => $this->maxCount(),
        ]);
    }
}
