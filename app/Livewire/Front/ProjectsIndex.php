<?php

namespace App\Livewire\Front;

use App\Services\Project\ProjectService;
use App\Traits\WithIncrementalIndex;
use Illuminate\Pagination\Paginator;
use Livewire\Component;

class ProjectsIndex extends Component
{
    use WithIncrementalIndex;

    protected function sortableFields(): array
    {
        return ['title', 'group', 'date'];
    }

    protected function getPage(): Paginator
    {
        return app(ProjectService::class)->getPublicIndexPage([
            'sort' => $this->sort,
            'order' => $this->order,
        ], $this->page);
    }

    public function hydrateRecords(): void
    {
        $this->records = app(ProjectService::class)->getPublicIndexRecords(
            $this->records->pluck('id')->all(),
        );
    }

    public function render()
    {
        return view('livewire.front.projects-index');
    }
}
