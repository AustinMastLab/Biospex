<?php

namespace App\Livewire\Front;

use App\Services\Project\ProjectService;
use Livewire\Component;

class ProjectsIndex extends Component
{
    public string $sort = 'date';

    public string $order = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }
    }

    public function render(ProjectService $projectService)
    {
        $projects = $projectService->getPublicIndexCachedData([
            'sort' => $this->sort,
            'order' => $this->order,
        ]);

        return view('livewire.front.projects-index', [
            'projects' => $projects,
        ]);
    }
}
