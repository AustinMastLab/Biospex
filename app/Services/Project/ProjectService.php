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

namespace App\Services\Project;

use App\Models\Project;
use App\Models\ProjectAsset;
use App\Models\User;
use App\Services\Helpers\CountService;
use App\Services\Helpers\DateService;
use App\Services\Trait\EventPartitionTrait;
use App\Services\Trait\ExpeditionPartitionTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    use EventPartitionTrait, ExpeditionPartitionTrait;

    /**
     * ProjectService constructor.
     */
    public function __construct(
        protected Project $project,
        protected ProjectAsset $projectAsset,
        protected CountService $countService,
        protected DateService $dateService,
    ) {}

    /**
     * Get select for project.
     *
     * @return array|string[]
     */
    public function getProjectEventSelect(): array
    {
        $results = $this->project->has('panoptesProjects')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->pluck('title', 'id');

        return ['' => 'Select'] + $results->toArray();
    }

    /**
     * Override create in base repository.
     *
     * @return \App\Models\Project|\Illuminate\Database\Eloquent\Model|true
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model|bool|Project
    {
        // Handle logo upload for new projects
        $this->handleLogoUploadForCreate($data);

        $project = $this->project->create($data);

        if (! isset($data['assets'])) {
            return $project;
        }

        $assets = collect($data['assets'])->reject(function ($asset) {
            return $this->filterOrDeleteAssets($asset);
        })->map(function ($asset) {
            return $this->projectAsset::make($asset);
        });

        $project->assets()->saveMany($assets->all());

        return $project;
    }

    /**
     * Handle logo upload for new projects.
     */
    private function handleLogoUploadForCreate(array &$data): void
    {
        // If logo_path is empty, remove it from data so it doesn't overwrite with null
        if (isset($data['logo_path']) && empty($data['logo_path'])) {
            unset($data['logo_path']);
        }
    }

    /**
     * Update project.
     */
    public function update(array $data, Project $project): array|bool
    {
        // Handle logo upload and removal
        $this->handleLogoUpload($data, $project);

        $project->fill($data)->save();

        if (! isset($data['assets'])) {
            return true;
        }

        $assets = collect($data['assets'])->reject(function ($asset) {
            return $this->filterOrDeleteAssets($asset);
        })->reject(function ($asset) {
            return ! empty($asset['id']) && $this->updateProjectAsset($asset);
        })->map(function ($asset) {
            return $this->projectAsset::make($asset);
        });

        if ($assets->isEmpty()) {
            return true;
        }

        return $project->assets()->saveMany($assets->all());
    }

    /**
     * Handle logo upload and remove old logo if new one is uploaded.
     */
    private function handleLogoUpload(array &$data, Project $project): void
    {
        // Check if there's a new logo uploaded via Livewire
        if (isset($data['logo_path']) && ! empty($data['logo_path'])) {
            // Remove old logo if it exists
            $this->removeOldLogo($project);

            // The new logo_path will be set via $project->fill($data)
        }

        // If logo_path is empty but was set before, keep the existing one
        if (isset($data['logo_path']) && empty($data['logo_path']) && ! empty($project->logo_path)) {
            unset($data['logo_path']); // Don't overwrite with empty value
        }
    }

    /**
     * Remove old logo files from storage.
     */
    private function removeOldLogo(Project $project): void
    {
        if (empty($project->logo_path)) {
            return;
        }

        try {
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

            // Remove the main logo file
            if (\Storage::disk($disk)->exists($project->logo_path)) {
                \Storage::disk($disk)->delete($project->logo_path);
            }

            // Remove any variants if they exist (for future use)
            $logoDirectory = dirname($project->logo_path);
            $logoFilename = basename($project->logo_path);

            // Check for variant directories (medium, small, etc.)
            $variantDirs = ['medium', 'small'];
            foreach ($variantDirs as $variant) {
                $variantPath = $logoDirectory.'/'.$variant.'/'.$logoFilename;
                if (\Storage::disk($disk)->exists($variantPath)) {
                    \Storage::disk($disk)->delete($variantPath);
                }
            }

        } catch (\Exception $e) {
            // Log error but don't fail the update
            \Log::error("Failed to remove old logo for project {$project->id}: ".$e->getMessage());
        }
    }

    /**
     * Get projects for admin index page.
     */
    public function getAdminIndex(User $user, array $request = []): Collection
    {
        $records = $this->project->withCount('expeditions')
            ->withSum('expeditionStats', 'transcriptions_completed')
            ->with([
                'group' => function ($q) use ($user) {
                    $q->whereHas('users', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                },
            ])->whereHas('group', function ($q) use ($user) {
                $q->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })->get();

        return $this->sortResults($records, $request);
    }

    /**
     * Cache key for the DATA collection of public projects.
     */
    protected function publicIndexDataCacheKey(array $request = []): string
    {
        $version = (int) Cache::get('public_sort:projects:version', 1);

        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return sprintf(
            'public_sort:projects_data:v%d:locale=%s:sort=%s:order=%s',
            $version,
            app()->getLocale(),
            $sort,
            $order,
        );
    }

    /**
     * Get cached DATA (Collection) for the public project index.
     * Must call the existing public query method and not duplicate it.
     */
    public function getPublicIndexCachedData(array $params = []): Collection
    {
        $cacheKey = $this->publicIndexDataCacheKey($params);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($params) {
            return $this->getPublicIndex($params);
        });
    }

    /**
     * Get a public project index page (SQL-sorted).
     */
    public function getPublicIndex(array $request = []): Collection
    {
        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $this->project
            ->newQuery()
            ->with('group')
            ->withCount(['expeditions', 'events'])
            ->withSum('expeditionStats', 'transcriptions_completed')
            ->has('panoptesProjects');

        if ($sort === 'group') {
            $query
                ->leftJoin('groups', 'groups.id', '=', 'projects.group_id')
                ->addSelect('projects.*')
                ->orderBy('groups.title', $order);
        } elseif ($sort === 'title') {
            $query->orderBy('projects.title', $order);
        } else {
            // date (default)
            $query->orderBy('projects.created_at', $order);
        }

        return $query->get();
    }

    /**
     * Get project for show page.
     */
    public function getProjectShow(Project &$project): array
    {
        $project->loadCount('expeditions')
            ->loadSum('expeditionStats', 'transcriptions_completed')
            ->loadSum('expeditionStats', 'transcriber_count')
            ->load([
                'group',
                'ocrQueue',
                'expeditions' => function ($q) {
                    $q->with(['stat', 'zooniverseExport', 'panoptesProject', 'workflowManager']);
                },
            ]);

        [$expeditions, $expeditionsCompleted] = $this->partitionExpeditions($project->expeditions);

        return [
            'project' => $project,
            'group' => $project->group,
            'expeditions' => $expeditions,
            'expeditionsCompleted' => $expeditionsCompleted,
        ];
    }

    /**
     * Get project page by slug.
     */
    public function getProjectPageBySlug($slug): ?Project
    {
        return $this->project->withCount('events')
            ->withCount('expeditions')
            ->withSum('expeditionStats', 'transcriptions_completed')
            ->with([
                'amChart',
                'group.users.profile',
                'assets',
                'lastPanoptesProject',
                'bingos',
                'expeditions' => function ($query) {
                    $query->has('panoptesProject')->whereHas('actors', function ($q) {
                        $q->zooniverse();
                    })->with('panoptesProject', 'stat', 'zooActorExpedition');
                },
                'events' => function ($q) {
                    $q->with('teams');
                    $q->orderBy('start_date', 'desc');
                }])->where('slug', '=', $slug)->first();
    }

    /**
     * Get project for deletion.
     */
    public function loadRelationsForDelete(Project &$project): void
    {
        $project->load([
            'group',
            'panoptesProjects',
            'workflowManagers',
            'expeditions.downloads',
        ]);
    }

    /**
     * Filter or delete asset.
     */
    public function filterOrDeleteAssets($asset): bool
    {
        if ($asset['type'] === null) {

            return true;
        }

        if (strtolower($asset['type']) === 'delete') {
            ProjectAsset::destroy($asset['id']);

            return true;
        }

        return false;
    }

    /**
     * Update project asset.
     */
    public function updateProjectAsset($asset): bool
    {
        $record = ProjectAsset::find($asset['id']);
        $record->type = $asset['type'];
        $record->name = $asset['name'];
        $record->description = $asset['description'];
        if (isset($asset['download'])) {
            $record->download = $asset['download'];
        }

        $record->save();

        return true;
    }

    /**
     * Sort results from index pages.
     */
    protected function sortResults(Collection $records, array $request = []): Collection
    {
        if (! isset($request['order'])) {
            return $records->sortBy('created_at');
        }

        match ($request['sort']) {
            'title' => $results = $request['order'] === 'desc' ?
                $records->sortByDesc('title') :
                $records->sortBy('title'),
            'group' => $results = $request['order'] === 'desc' ?
                $records->sortByDesc(fn ($project) => $project->group->title) :
                $records->sortBy(fn ($project) => $project->group->title),
            'date' => $results = $request['order'] === 'desc' ?
                $records->sortByDesc('created_at') :
                $records->sortBy('created_at'),
        };

        return $results;
    }

    /**
     * Get project for Darwin import job.
     */
    public function getProjectForDarwinImportJob($projectId): ?Project
    {
        return $this->project->with(['group' => function ($q) {
            $q->with(['owner', 'users' => function ($q) {
                $q->where('notification', 1);
            }]);
        }])->find($projectId);
    }
}
