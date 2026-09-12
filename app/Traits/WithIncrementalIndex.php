<?php

namespace App\Traits;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

trait WithIncrementalIndex
{
    public string $type = 'active';

    public string $sort = 'date';

    public string $order = 'asc';

    public ?int $projectId = null;

    public int $page = 1;

    public bool $hasMore = false;

    public Collection $records;

    public function mount(?string $type = null, ?int $projectId = null): void
    {
        if ($type !== null) {
            $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';
        }

        if ($projectId !== null) {
            $this->projectId = $projectId;
        }

        $this->resetRecords();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }

        $this->resetRecords();
    }

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';

        $this->resetRecords();
        $this->typeChanged();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->page++;
        $records = $this->getPage();

        $this->records = $this->records->concat($records->items());
        $this->hasMore = $records->hasMorePages();
    }

    protected function resetRecords(): void
    {
        $this->page = 1;
        $records = $this->getPage();

        $this->records = collect($records->items());
        $this->hasMore = $records->hasMorePages();
    }

    /**
     * @return array<int, string>
     */
    abstract protected function sortableFields(): array;

    abstract protected function getPage(): Paginator;

    protected function typeChanged(): void {}
}
