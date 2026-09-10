<?php

namespace App\Traits;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

trait WithIncrementalExpeditions
{
    public string $type = 'active';

    public string $sort = 'date';

    public string $order = 'asc';

    public ?int $projectId = null;

    public int $page = 1;

    public bool $hasMore = false;

    public Collection $expeditions;

    public function mount(?string $type = null, ?int $projectId = null): void
    {
        if ($type !== null) {
            $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';
        }

        if ($projectId !== null) {
            $this->projectId = $projectId;
        }

        $this->resetExpeditions();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableExpeditionFields(), true)) {
            return;
        }

        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }

        $this->resetExpeditions();
    }

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';

        $this->resetExpeditions();
        $this->typeChanged();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->page++;
        $expeditions = $this->getExpeditionPage();

        $this->expeditions = $this->expeditions->concat($expeditions->items());
        $this->hasMore = $expeditions->hasMorePages();
    }

    protected function resetExpeditions(): void
    {
        $this->page = 1;
        $expeditions = $this->getExpeditionPage();

        $this->expeditions = collect($expeditions->items());
        $this->hasMore = $expeditions->hasMorePages();
    }

    /**
     * @return array<int, string>
     */
    abstract protected function sortableExpeditionFields(): array;

    abstract protected function getExpeditionPage(): Paginator;

    protected function typeChanged(): void {}
}
