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

namespace App\Services\Actor\TesseractOcr;

use App\Enums\OcrQueueStage;
use App\Models\Expedition;
use App\Models\OcrQueue;
use App\Models\OcrQueueFile;
use App\Models\Project;
use App\Services\Subject\SubjectService;
use App\Traits\ButtonTrait;

/**
 * Class OcrService
 */
class TesseractOcrBuild
{
    use ButtonTrait;

    /**
     * Ocr constructor.
     */
    public function __construct(
        protected OcrQueue $ocrQueue,
        protected OcrQueueFile $ocrQueueFile,
        protected SubjectService $subjectService,
    ) {}

    /**
     * Get subject count for an ocr process.
     */
    public function getSubjectCountForOcr(Project $project, ?Expedition $expedition = null): int
    {
        return $this->subjectService->getSubjectCountForOcr($project, $expedition);
    }

    /**
     * Create ocr queue record.
     */
    public function createOcrQueue(Project $project, ?Expedition $expedition, int $total): OcrQueue
    {
        return $this->ocrQueue->create([
            'project_id' => $project->id,
            'expedition_id' => $expedition?->id,
            'total' => $total,
            'queued' => 0,
            'stage' => OcrQueueStage::Waiting,
            'files_ready' => 0,
            'error' => 0,
        ]);
    }

    /**
     * Create ocr queue files.
     */
    public function createOcrQueueFiles(OcrQueue $queue, Project $project, ?Expedition $expedition = null): void
    {
        $subjects = $this->subjectService->getSubjectCursorForOcr($project, $expedition);

        $subjects->chunk(1000)->each(function ($chunk) use ($queue) {
            $filesData = $chunk->map(function ($subject) use ($queue) {
                return [
                    'queue_id' => $queue->id,
                    'subject_id' => (string) $subject->_id,
                    'access_uri' => $subject->accessURI,
                ];
            })->toArray();

            $this->ocrQueueFile->upsert($filesData, ['queue_id', 'subject_id'], ['access_uri']);

            return true;  // Continue chunking
        });

        $queue->files_ready = 1;
        $queue->save();
    }
}
