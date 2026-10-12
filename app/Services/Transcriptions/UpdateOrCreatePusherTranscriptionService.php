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

namespace App\Services\Transcriptions;

use App\Facades\TranscriptionMapHelper;
use Validator;

/**
 * Class UpdateOrCreatePusherTranscriptionService
 */
class UpdateOrCreatePusherTranscriptionService
{
    /**
     * UpdateOrCreatePusherTranscriptionService constructor.
     */
    public function __construct(
        protected PusherTranscriptionService $pusherTranscriptionService,
        protected PanoptesTranscriptionService $panoptesTranscriptionService
    ) {}

    /**
     * Get transcriptions.
     *
     * @param  null  $timestamp
     */
    public function getTranscriptions(int $expeditionId, $timestamp = null): mixed
    {
        return $this->panoptesTranscriptionService->getTranscriptionsForDashboardJob($expeditionId, $timestamp);
    }

    /**
     * Process transcripts
     * Uses transcriptions from overnight job to update any existing,
     * or create new, pusher transcriptions.
     *
     * @throws \Exception
     */
    public function processTranscripts($transcription, $expedition)
    {
        $classification = $this->pusherTranscriptionService->findBy('classification_id', $transcription->classification_id);
        $classification === null ?
            $this->createClassification($transcription, $expedition) :
            $this->updateClassification($transcription, $classification, $expedition);
    }

    /**
     * Create classification if it doesn't exist.
     *
     * @throws \Exception
     */
    private function createClassification($transcription, $expedition)
    {
        $thumbnailUri = $this->setThumbnailUri($transcription);

        $classification_id = (int) $transcription->classification_id;

        if ($this->validateTranscription($classification_id)) {
            return;
        }

        $item = [
            'classification_id' => $classification_id,
            'expedition_uuid' => $expedition->uuid,
            'project' => $expedition->panoptesProject->title,
            'description' => $expedition->description,
            'guid' => \Str::uuid()->toString(),
            'timestamp' => $transcription->classification_finished_at,
            'subject' => [
                'link' => $transcription->subject_references,
                'thumbnailUri' => $thumbnailUri,
            ],
            'contributor' => [
                'decimalLatitude' => '',
                'decimalLongitude' => '',
                'ipAddress' => '',
                'transcriber' => $transcription->user_name,
                'physicalLocation' => [
                    'country' => '',
                    'province' => '',
                    'county' => '',
                    'municipality' => '',
                    'locality' => '',
                ],
            ],
            'transcriptionContent' => [
                'lat' => '',
                'long' => '',
                'country' => $transcription->Country,
                'province' => TranscriptionMapHelper::mapTranscriptionField('province', $transcription),
                'county' => $transcription->County,
                'municipality' => '',
                'locality' => $transcription->Location,
                'date' => '', // which date to use? transcription date is messy
                'collector' => TranscriptionMapHelper::mapTranscriptionField('collector', $transcription),
                'taxon' => TranscriptionMapHelper::mapTranscriptionField('taxon', $transcription),
            ],
            'discretionaryState' => 'Transcribed',
        ];

        $this->pusherTranscriptionService->create($item);
    }

    /**
     * Update Classification.
     */
    private function updateClassification($transcription, $classification, $expedition)
    {
        $thumbnailUri = $this->setThumbnailUri($transcription);

        $subject = [
            'link' => ! empty($transcription->subject_references) ? $transcription->subject_references : $classification->subject['link'],
            'thumbnailUri' => ! empty($thumbnailUri) ? $thumbnailUri : $classification->subject['thumbnailUri'],
        ];

        $transcriptionContent = [
            'country' => ! empty($transcription->Country) ? $transcription->Country : $classification->transcriptionContent['country'],
            'province' => TranscriptionMapHelper::mapTranscriptionField('province', $transcription, $classification),
            'county' => ! empty($transcription->County) ? $transcription->County : $classification->transcriptionContent['county'],
            'locality' => ! empty($transcription->Location) ? $transcription->Location : '',
            'collector' => TranscriptionMapHelper::mapTranscriptionField('collector', $transcription, $classification),
            'taxon' => TranscriptionMapHelper::mapTranscriptionField('taxon', $transcription, $classification),
        ];

        $attributes = [
            'expedition_uuid' => $expedition->uuid,
            'timestamp' => $transcription->classification_finished_at,
            'subject' => array_merge($classification->subject, $subject),
            'contributor' => array_merge($classification->contributor, ['transcriber' => $transcription->user_name]),
            'transcriptionContent' => array_merge($classification->transcriptionContent, $transcriptionContent),
        ];

        $this->pusherTranscriptionService->update($attributes, $classification->_id);
    }

    /**
     * Determine image url.
     */
    private function setThumbnailUri($transcription): mixed
    {
        return (empty($transcription->subject_imageURL)) ? $transcription->subject_accessURI : $transcription->subject_imageURL;
    }

    /**
     * Validate transcription to prevent duplicates.
     */
    public function validateTranscription($classification_id): mixed
    {

        $rules = ['classification_id' => 'unique:mongodb.pusher_transcriptions,classification_id'];
        $values = ['classification_id' => $classification_id];
        $validator = Validator::make($values, $rules);
        $validator->getPresenceVerifier()->setConnection('mongodb');

        // returns true if failed.
        return $validator->fails();
    }
}
