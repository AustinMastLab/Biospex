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

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/**
 * Search, sorting, entries per page and paging for a Livewire table, done on the server.
 *
 * The component lists its sortable columns in sortableFields() and gives a page name of its own
 * in pageName(), so several tables on one page keep separate pages in the URL.
 */
trait WithDataTable
{
    use WithPagination;

    public string $search = '';

    public string $sortField = '';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    /**
     * Columns the table can sort by.
     *
     * @return array<int, string>
     */
    abstract protected function sortableFields(): array;

    /**
     * The query string key for this table's page.
     */
    abstract public function pageName(): string;

    /**
     * Choices for the "Show N entries" picker.
     *
     * @return array<int, int>
     */
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    public function updatedSearch(): void
    {
        $this->resetPage($this->pageName());
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = $this->perPageOptions()[0];
        }

        $this->resetPage($this->pageName());
    }

    /**
     * Sort by a column, or reverse the order when it is already the sort column.
     */
    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage($this->pageName());
    }

    /**
     * The search text, trimmed.
     */
    protected function searchTerm(): string
    {
        return trim($this->search);
    }

    /**
     * Sort, search and page rows that are already in memory.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $searchFields
     */
    protected function paginateCollection(Collection $rows, array $searchFields): LengthAwarePaginator
    {
        $term = mb_strtolower($this->searchTerm());

        if ($term !== '') {
            $rows = $rows->filter(function (array $row) use ($term, $searchFields): bool {
                foreach ($searchFields as $field) {
                    if (str_contains(mb_strtolower((string) ($row[$field] ?? '')), $term)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($this->sortField !== '') {
            $rows = $rows->sortBy($this->sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->sortDirection === 'desc');
        }

        $page = max(1, (int) $this->getPage($this->pageName()));
        $lastPage = max(1, (int) ceil($rows->count() / $this->perPage));
        $page = min($page, $lastPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $page,
            ['pageName' => $this->pageName()]
        );
    }
}
