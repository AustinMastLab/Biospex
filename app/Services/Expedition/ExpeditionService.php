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

namespace App\Services\Expedition;

use App\Models\Expedition;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ZooniverseNewExpedition;
use App\Services\Subject\SubjectService;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExpeditionService
{
    /**
     * Create a new instance of ExpeditionService.
     */
    public function __construct(
        protected Expedition $expedition,
        protected SubjectService $subjectService
    ) {}

    /**
     * Create the expedition in MySQL.
     *
     * Subjects are applied afterwards with saveSubjects(), outside the MySQL
     * transaction, because MongoDB cannot take part in it.
     *
     * @param  array<string, mixed>  $request
     *
     * @throws \Throwable
     */
    public function store(Project $project, array $request): Expedition
    {
        $this->handleLogoUploadForCreate($request);

        $request['project_id'] = $project->id;

        return DB::transaction(function () use ($request) {
            $expedition = Expedition::create($request);
            $this->syncStat($expedition, 0);

            return $expedition;
        });
    }

    /**
     * Apply the submitted subject selection to MongoDB and refresh the subject totals.
     *
     * Safe to repeat: resubmitting the same selection changes nothing, so a
     * failure here can be fixed by submitting the form again.
     */
    public function saveSubjects(Expedition $expedition, ?string $subjectIds): int
    {
        $expedition->load(['actors', 'workflow.actors', 'workflowManager']);

        $subjectCount = $this->syncSubjects($expedition, $this->parseSubjectIds($subjectIds));
        $this->syncStat($expedition, $subjectCount);
        $this->syncActors($expedition, $subjectCount);

        return $subjectCount;
    }

    /**
     * Get one authorization-scoped page of expeditions for the admin index.
     */
    public function getAdminIndexPage(User $user, array $request = [], int $page = 1): Paginator
    {
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sortField = $request['sort'] ?? 'date';
        $sort = in_array($sortField, ['title', 'project', 'date'], true)
            ? $sortField
            : 'date';
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? null;

        $query = $this->expedition->newQuery()
            ->with(['project.group', 'stat', 'panoptesProject', 'workflowManager', 'zooniverseExport'])
            ->where('expeditions.completed', $type === 'completed');

        if (! $user->isAdmin()) {
            $query->whereHas('project.group.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        if (! empty($projectId)) {
            $query->where('expeditions.project_id', $projectId);
        }

        if ($sort === 'project') {
            $query->join('projects', 'projects.id', '=', 'expeditions.project_id')
                ->select('expeditions.*')
                ->orderBy('projects.title', $order);
        } elseif ($sort === 'title') {
            $query->orderBy('expeditions.title', $order);
        } else {
            $query->orderBy('expeditions.created_at', $order);
        }

        return $query
            ->orderBy('expeditions.id', $order)
            ->simplePaginate(12, ['*'], 'expeditionPage', $page);
    }

    /**
     * Cache key for one page of public expeditions.
     */
    protected function publicIndexPageCacheKey(array $request, int $page): string
    {
        $version = (int) Cache::get('public_sort:expeditions:version', 1);
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sort = ($request['sort'] ?? 'date') === 'title' ? 'title' : 'date';
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? null;

        return sprintf(
            'public_sort:expeditions_page:v%d:locale=%s:type=%s:sort=%s:order=%s:project=%s:page=%d',
            $version,
            app()->getLocale(),
            $type,
            $sort,
            $order,
            empty($projectId) ? 'all' : (string) $projectId,
            $page,
        );
    }

    /**
     * Get one page of public expeditions for the selected filter and sort order.
     */
    public function getPublicIndexPage(array $request = [], int $page = 1): Paginator
    {
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sort = ($request['sort'] ?? 'date') === 'title' ? 'title' : 'date';
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? null;
        $cacheKey = $this->publicIndexPageCacheKey($request, $page);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($projectId, $type, $sort, $order, $page) {
            $query = $this->publicIndexQuery($projectId)
                ->where('expeditions.completed', $type === 'completed');

            if ($sort === 'title') {
                $query->orderBy('expeditions.title', $order);
            } else {
                $query->orderBy('expeditions.created_at', $order);
            }

            return $query
                ->orderBy('expeditions.id', $order)
                ->simplePaginate(12, ['*'], 'expeditionPage', $page);
        });
    }

    /**
     * Build the shared public expedition query with card relationships.
     */
    protected function publicIndexQuery(?int $projectId = null): Builder
    {
        $query = $this->expedition
            ->newQuery()
            ->with('project:id,title,slug')
            ->has('panoptesProject')
            ->whereHas('actors', function ($q) {
                $q->zooniverse();
            })
            ->with('panoptesProject', 'stat', 'zooActorExpedition');

        if (! empty($projectId)) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }

    /**
     * Normalize the comma-separated subject ids posted by the subject grid.
     */
    public function parseSubjectIds(?string $subjectIds): Collection
    {
        return collect(explode(',', (string) $subjectIds))
            ->map(fn (string $subjectId) => trim($subjectId))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get subject ids assigned to expedition.
     */
    public function getSubjectIdsByExpeditionId(Expedition $expedition): Collection
    {
        return $this->subjectService->getIdsByExpeditionId($expedition->id);
    }

    /**
     * Apply a subject selection to MongoDB and return how many subjects are now assigned.
     *
     * Subjects are left unchanged once a workflow manager exists. The returned
     * count is read back from MongoDB, so it is correct even on a retry.
     */
    public function syncSubjects(Expedition $expedition, Collection $subjectIds): int
    {
        if ($expedition->workflowManager === null) {
            $currentIds = $this->getSubjectIdsByExpeditionId($expedition);

            $this->subjectService->detachSubjects($currentIds->diff($subjectIds), $expedition->id);
            $this->subjectService->attachSubjects($subjectIds->diff($currentIds), $expedition->id);
        }

        return $this->subjectService->countByExpeditionId($expedition->id);
    }

    public function syncActors(Expedition $expedition, int $subjectCount): void
    {
        if (! $expedition->workflow) {
            return;
        }

        if (! $expedition->workflow->actors || $expedition->workflow->actors->isEmpty()) {
            return;
        }

        $actors = $expedition->workflow->actors->mapWithKeys(function ($actor) use ($expedition, $subjectCount) {
            $isExistingActor = $expedition->actors->contains('id', $actor->id);

            if ($isExistingActor) {
                return [$actor->id => ['order' => $actor->pivot->order]];
            } else {
                return [
                    $actor->id => [
                        'state' => 0, 'order' => $actor->pivot->order, 'total' => $subjectCount,
                    ],
                ];
            }
        })->toArray();

        $expedition->actors()->sync($actors);
    }

    public function syncStat(Expedition $expedition, int $subjectCount): void
    {
        $expedition->stat()->updateOrCreate(
            ['expedition_id' => $expedition->id],
            ['local_subject_count' => $subjectCount]
        );
    }

    /**
     * Send notifications for new projects and actors.
     *
     * @see ZooniverseNewExpedition
     */
    public function notifyActorContacts($expedition, $project): void
    {
        $newNotification = config('zooniverse.new_expedition_notification');

        $expedition->workflow->actors->reject(function ($actor) {
            return $actor->contacts->isEmpty();
        })->filter(function ($actor) use ($newNotification) {
            return isset($newNotification[$actor->id]);
        })->each(function ($actor) use ($project, $expedition, $newNotification) {
            $class = '\App\Notifications\\'.$newNotification[$actor->id];
            if (class_exists($class)) {
                Notification::send($actor->contacts, new $class($project, $expedition));
            }
        });
    }

    /**
     * Update the expedition in MySQL.
     *
     * If the expedition is completed and unlocked, this is a first change.
     * Subjects are applied afterwards with saveSubjects().
     *
     * @param  array<string, mixed>  $request
     *
     * @throws \Throwable
     */
    public function update(Expedition $expedition, array $request): Expedition
    {
        $this->handleLogoUpload($request, $expedition);

        return DB::transaction(function () use ($expedition, $request) {
            $expedition->completed = $this->setExpeditionCompleted($expedition, $request['workflow_id']);

            $expedition->fill($request)->save();

            return $expedition;
        });
    }

    /**
     * Reset Expedition completed according to workflow chosen.
     */
    private function setExpeditionCompleted(Expedition $expedition, int $workflow_id): int
    {
        return ($expedition->completed && ! $expedition->locked && $workflow_id == config('geolocate.workflow_id')) ? 0 : $expedition->completed;
    }

    /**
     * Get expeditions for Zooniverse processing.
     */
    public function getExpeditionsForZooniverseProcess(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->expedition->whereHas('panoptesProject')->whereHas('actors', function ($q) {
            $q->zooniverse();
        })->where('completed', 0)->get();
    }

    /**
     * Get expedition download by actor.
     */
    public function getExpeditionDownloadsByActor(Expedition &$expedition): Model
    {
        return $expedition->load([
            'project.group', 'actors.downloads' => function ($query) use ($expedition) {
                $query->where('expedition_id', $expedition->id);
            },
        ]);
    }

    /**
     * Get expedition for home page visuals.
     */
    public function getHomePageProjectExpedition(): ?Model
    {
        return $this->expedition->with([
            'project' => function ($q) {
                $q->withCount('expeditions')->withSum('expeditionStats',
                    'transcriptions_completed')->withSum('expeditionStats',
                        'transcriber_count')->withCount('events');
            },
        ])->with('panoptesProject')->whereHas('stat', function ($q) {
            $q->whereBetween('percent_completed', [0.00, 99.99]);
        })->with([
            'stat' => function ($q) {
                $q->whereBetween('percent_completed', [0.00, 99.99]);
            },
        ])->where('project_id', 13)->inRandomOrder()->first();
    }

    /**
     * Get expedition for Zooniverse process.
     *
     * @see ZooniverseCsvService::getExpedition()
     */
    public function getExpeditionForZooniverseProcess(int $expeditionId): Model
    {
        return $this->expedition->with(['panoptesProject'])->has('panoptesProject')->whereHas('actors', function ($q) {
            $q->zooniverse();
        })->where('completed', 0)->find($expeditionId);
    }

    /**
     * Get expedition for queue reset.
     */
    public function getExpeditionForQueueReset(int $expeditionId): Expedition
    {
        return $this->expedition->with(['zooActorExpedition.actor', 'stat', 'exportQueue'])->find($expeditionId);
    }

    /**
     * Handle logo upload for new expeditions.
     */
    private function handleLogoUploadForCreate(array &$data): void
    {
        // If logo_path is empty, remove it from data so it doesn't overwrite with null
        if (isset($data['logo_path']) && empty($data['logo_path'])) {
            unset($data['logo_path']);
        }
    }

    /**
     * Handle logo upload and remove old logo if new one is uploaded.
     */
    private function handleLogoUpload(array &$data, Expedition $expedition): void
    {
        // Check if there's a new logo uploaded via Livewire
        if (! empty($data['logo_path'])) {
            // Remove old logo if it exists
            $this->removeOldLogo($expedition);
            // The new logo_path will be set via $expedition->fill($data)
        }

        // If logo_path is empty but was set before, keep the existing one
        if (isset($data['logo_path']) && empty($data['logo_path']) && ! empty($expedition->logo_path)) {
            unset($data['logo_path']); // Don't overwrite with empty value
        }
    }

    /**
     * Remove old logo files from storage.
     */
    private function removeOldLogo(Expedition $expedition): void
    {
        if (empty($expedition->logo_path)) {
            return;
        }

        try {
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

            if (\Storage::disk($disk)->exists($expedition->logo_path)) {
                \Storage::disk($disk)->delete($expedition->logo_path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the update
            \Log::error("Failed to remove old logo for expedition {$expedition->id}: ".$e->getMessage());
        }
    }
}
