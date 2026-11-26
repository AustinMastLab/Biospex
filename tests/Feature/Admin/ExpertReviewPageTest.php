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

use App\Facades\TranscriptionMapHelper;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\PanoptesTranscription;
use App\Models\Reconcile;
use App\Models\User;
use App\Services\Api\PanoptesApiService;
use App\Services\Api\ZooniverseTalkApiService;

beforeEach(function () {
    Reconcile::truncate();
    PanoptesTranscription::truncate();
});

afterEach(function () {
    Reconcile::truncate();
    PanoptesTranscription::truncate();
});

it('shows each participant\'s answer as text, and "left blank" for a participant without the column', function () {
    $expedition = Expedition::factory()->create();
    PanoptesProject::factory()->create(['project_id' => $expedition->project_id, 'expedition_id' => $expedition->id, 'panoptes_project_id' => 77]);
    $user = User::factory()->verified()->create();
    $user->groups()->attach($expedition->project->group);

    $country = TranscriptionMapHelper::encodeTranscriptionField('Country');
    Reconcile::create([
        'subject_id' => 101,
        'subject_expeditionId' => $expedition->id,
        'subject_problem' => 1,
        'subject_columns' => $country,
        'subject_imageName' => 'image.jpg',
        $country => 'United States',
    ]);
    // One participant answered with markup; the other's transcription has no Country field.
    PanoptesTranscription::create(['classification_id' => 1, 'subject_id' => 101, 'user_name' => 'first', 'subject_projectId' => $expedition->project_id, $country => '<b>Canada</b>']);
    PanoptesTranscription::create(['classification_id' => 2, 'subject_id' => 101, 'user_name' => 'second', 'subject_projectId' => $expedition->project_id]);

    $this->mock(ZooniverseTalkApiService::class)->shouldReceive('getComments')->andReturn([]);
    $this->mock(PanoptesApiService::class)->shouldReceive('getSubjectImageLocation')->andReturn('https://images.example.test/101.jpg');

    $this->actingAs($user)
        ->get(route('admin.reconciles.index', $expedition))
        ->assertOk()
        ->assertSee('&lt;b&gt;Canada&lt;/b&gt;', false)
        ->assertDontSee('<b>Canada</b>', false)
        ->assertSee(t('participant left blank'));
});
