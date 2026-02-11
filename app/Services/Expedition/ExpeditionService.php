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
use App\Services\Subject\SubjectService;
use App\Services\Trait\ExpeditionPartitionTrait;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExpeditionService
{
    use ExpeditionPartitionTrait;

    private Collection $subjectIds;

    /**
     * Create a new instance of ExpeditionService.
     */
    public function __construct(
        protected Expedition $expedition,
        protected SubjectService $subjectService
    ) {}

    /**
     * Create Expedition and return.
     *
     * @throws \Throwable
     */
    public function store(Project $project, array $request): mixed
    {
        // Handle logo upload for new expeditions
        $this->handleLogoUploadForCreate($request);

        $request['project_id'] = $project->id;

        $expedition = DB::transaction(function () use ($project, $request) {
            $expedition = Expedition::create($request);
            $expedition->load(['project', 'workflow.actors.contacts']);
            $this->setSubjectIds($request['subject-ids']);
            $this->attachSubjects($expedition->id);

            try {
                $this->syncActors($expedition);
                $this->syncStat($expedition);
            } catch (\Exception $e) {
                Log::error('Failed during sync operations in transaction', [
                    'expedition_id' => $expedition->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
                ]);
                $this->rollbackSubjects($expedition->id);
                throw $e; // Re-throw to trigger MySQL rollback
            }

            $this->notifyActorContacts($expedition, $project);

            return $expedition;
        });

        return $expedition;
    }

    /**
     * Rollback subject attachments for failed expedition creation.
     */
    private function rollbackSubjects(int $expeditionId): void
    {
        try {
            $this->subjectService->detachSubjects($this->subjectIds, $expeditionId);
        } catch (\Exception $e) {
            Log::error('Failed to rollback subjects during expedition creation failure', [
                'expedition_id' => $expeditionId, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rollback subject changes for failed expedition update.
     */
    private function rollbackSubjectChanges(int $expeditionId, Collection $originalSubjectIds): void
    {
        try {
            // Detach all current subjects
            $currentSubjectIds = $this->getSubjectIdsByExpeditionId($this->expedition->find($expeditionId));
            $this->subjectService->detachSubjects($currentSubjectIds, $expeditionId);

            // Reattach original subjects
            $this->subjectService->attachSubjects($originalSubjectIds, $expeditionId);
        } catch (\Exception $e) {
            Log::error('Failed to rollback subject changes during expedition update failure', [
                'expedition_id' => $expeditionId, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get expeditions for admin index.
     */
    public function getAdminIndex(User $user, array $request = []): Collection
    {
        $query = $this->expedition->with([
            'project.group', 'stat', 'panoptesProject', 'workflowManager', 'zooniverseExport',
        ])->whereHas('project.group.users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });

        $sortedRecords = $this->sortRecords($query, $request);

        return $this->partitionExpeditions($sortedRecords);
    }

    /**
     * Cache key for the DATA collection of public expeditions (both partitions).
     */
    protected function publicIndexDataCacheKey(array $request = []): string
    {
        $version = (int) Cache::get('public_sort:expeditions:version', 1);

        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? $request['id'] ?? null;

        return sprintf(
            'public_sort:expeditions_data:v%d:locale=%s:sort=%s:order=%s:project=%s',
            $version,
            app()->getLocale(),
            $sort,
            $order,
            empty($projectId) ? 'all' : (string) $projectId,
        );
    }

    /**
     * Get cached DATA (Collection of partitions) for the public expedition index.
     * Must call the existing public query method and not duplicate it.
     */
    public function getPublicIndexCachedData(array $params = []): Collection
    {
        // Normalize projectId for consistency (UI may send 'id')
        if (isset($params['id']) && ! isset($params['projectId'])) {
            $params['projectId'] = $params['id'];
        }

        $cacheKey = $this->publicIndexDataCacheKey($params);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($params) {
            return $this->getPublicIndex($params);
        });
    }

    /**
     * Get expeditions for public index (SQL-sorted).
     */
    public function getPublicIndex(array $request = []): Collection
    {
        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? null;

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

        if ($sort === 'title') {
            $query->orderBy('expeditions.title', $order);
        } else {
            // date (default)
            $query->orderBy('expeditions.created_at', $order);
        }

        $records = $query->get();

        return $this->partitionExpeditions($records);
    }

    /**
     * Sort results for expedition indexes.
     */
    protected function sortRecords(Builder $query, array $request = []): \Illuminate\Database\Eloquent\Collection
    {
        $records = ! isset($request['projectId']) ? $query->get() : $query->where('project_id',
            $request['projectId'])->get();

        if (! isset($request['order'])) {
            return $records;
        }

        match ($request['sort']) {
            'title' => $records = $request['order'] === 'desc' ? $records->sortByDesc('title') : $records->sortBy('title'),
            'project' => $records = $request['order'] === 'desc' ? $records->sortByDesc(fn ($expedition
            ) => $expedition->project->title) : $records->sortBy(fn ($expedition) => $expedition->project->title),
            'date' => $records = $request['order'] === 'desc' ? $records->sortByDesc('created_at') : $records->sortBy('created_at'),
        };

        return $records;
    }

    /**
     * Get subject id count.
     */
    public function getSubjectCount(): int
    {
        return $this->subjectIds->count();
    }

    /**
     * Set subject ids.
     */
    public function setSubjectIds(?string $subjectIds = null): void
    {
        $this->subjectIds = $subjectIds === null ? collect([]) : collect(explode(',', $subjectIds));
    }

    /**
     * Get subject ids assigned to expedition.
     */
    public function getSubjectIdsByExpeditionId(Expedition $expedition): Collection
    {
        return $this->subjectService->subject->where('expedition_ids', $expedition->id)->get(['_id'])->pluck('_id');
    }

    /**
     * Update subjects for expedition if changed and if workflow manager does not exist.
     */
    public function updateSubjects(Expedition $expedition): void
    {
        if ($expedition->workflowManager !== null) {
            return;
        }

        $oldIds = $this->getSubjectIdsByExpeditionId($expedition);
        $newIds = $this->subjectIds;

        $detachIds = $oldIds->diff($newIds);
        $attachIds = $newIds->diff($oldIds);

        $this->detachSubjects($expedition->id, $detachIds);
        $this->attachSubjects($expedition->id, $attachIds);

        $this->syncStat($expedition);
    }

    /**
     * Detach subjects from expedition.
     */
    public function detachSubjects(int $expeditionId, Collection $detachIds): void
    {
        $this->subjectService->detachSubjects($detachIds, $expeditionId);
    }

    /**
     * Attach subjects to expedition.
     */
    public function attachSubjects(int $expeditionId, ?Collection $attachIds = null): void
    {
        $attachIds = $attachIds === null ? $this->subjectIds : $attachIds;

        $this->subjectService->attachSubjects($attachIds, $expeditionId);
    }

    public function syncActors(Expedition $expedition): void
    {
        if (! $expedition->workflow) {
            return;
        }

        if (! $expedition->workflow->actors || $expedition->workflow->actors->isEmpty()) {
            return;
        }

        $subjectCount = $this->getSubjectCount();

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

        try {
            $expedition->actors()->sync($actors);
        } catch (\Exception $e) {
            Log::error('Failed to sync actors', [
                'expedition_id' => $expedition->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function syncStat(Expedition $expedition): void
    {
        $subjectCount = $this->getSubjectCount();

        try {
            // Simple updateOrCreate - much cleaner!
            $expedition->stat()->updateOrCreate(
                ['expedition_id' => $expedition->id],
                ['local_subject_count' => $subjectCount]
            );

            // Manually flush all Lada Cache

        } catch (\Exception $e) {
            Log::error('Failed to sync expedition stat', [
                'expedition_id' => $expedition->id, 'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send notifications for new projects and actors.
     *
     * @see \App\Notifications\ZooniverseNewExpedition
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
     * Update Expedition.
     * If the expedition is completed and unlocked, this is a first change. If workflow id
     *
     * @throws \Throwable
     */
    public function update(Expedition $expedition, array $request): Expedition
    {
        // Handle logo upload and removal
        $this->handleLogoUpload($request, $expedition);

        return DB::transaction(function () use ($expedition, $request) {
            try {
                $expedition->completed = $this->setExpeditionCompleted($expedition, $request['workflow_id']);

                $expedition->fill($request)->save();

                $expedition->load(['actors', 'workflow.actors', 'workflowManager']);

                $this->setSubjectIds($request['subject-ids']);

                // Store original subject IDs for potential rollback
                $originalSubjectIds = $this->getSubjectIdsByExpeditionId($expedition);

                $this->updateSubjects($expedition);

                $this->syncActors($expedition);
            } catch (\Exception $e) {
                Log::error('Failed during sync operations in update transaction', [
                    'expedition_id' => $expedition->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
                ]);

                // Rollback MongoDB changes - restore original subjects
                $this->rollbackSubjectChanges($expedition->id, $originalSubjectIds);
                throw $e; // Re-throw to trigger MySQL rollback
            }

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
    public function getExpeditionDownloadsByActor(Expedition &$expedition): \Illuminate\Database\Eloquent\Model
    {
        return $expedition->load([
            'project.group', 'actors.downloads' => function ($query) use ($expedition) {
                $query->where('expedition_id', $expedition->id);
            },
        ]);
    }

    /**
     * Find expedition having workflow manager by id.
     */
    public function findExpeditionHavingWorkflowManager($expeditionId): ?Expedition
    {
        return $this->expedition->has('workflowManager')->find($expeditionId);
    }

    /**
     * Get expedition for home page visuals.
     */
    public function getHomePageProjectExpedition(): ?\Illuminate\Database\Eloquent\Model
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
    public function getExpeditionForZooniverseProcess(int $expeditionId): \Illuminate\Database\Eloquent\Model
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

            // Remove the main logo file
            if (\Storage::disk($disk)->exists($expedition->logo_path)) {
                \Storage::disk($disk)->delete($expedition->logo_path);
            }

            // Remove any variants if they exist (medium, small, etc.)
            $logoDirectory = dirname($expedition->logo_path);
            $logoFilename = basename($expedition->logo_path);

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
            \Log::error("Failed to remove old logo for expedition {$expedition->id}: ".$e->getMessage());
        }
    }
}
