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

use App\Jobs\ScoreboardJob;
use App\Jobs\WeDigBioEventProgressJob;
use App\Jobs\ZooniversePusherJob;
use App\Models\Event;
use App\Models\EventTeam;
use App\Models\EventTranscription;
use App\Models\EventUser;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\PanoptesTranscription;
use App\Models\PusherTranscription;
use App\Models\Subject;
use App\Models\WeDigBioEvent;
use App\Models\WeDigBioEventTranscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    PanoptesTranscription::truncate();
    PusherTranscription::truncate();
    Subject::truncate();
    Queue::fake();
    $this->travelTo('2026-10-08 12:00:00 UTC');

    $expedition = Expedition::factory()->create();
    PanoptesProject::factory()->create(['expedition_id' => $expedition->id, 'title' => 'Herbarium Ledgers']);
    $this->expedition = Expedition::find($expedition->id);

    // A Biospex event on the expedition's project, with the volunteer on a team
    $event = Event::factory()->create([
        'project_id' => $this->expedition->project_id,
        'start_date' => Carbon::parse('2026-10-01 00:00:00', 'UTC'),
        'end_date' => Carbon::parse('2026-10-10 00:00:00', 'UTC'),
    ]);
    $team = EventTeam::factory()->create(['event_id' => $event->id]);
    $team->users()->attach(EventUser::factory()->create(['nfn_user' => 'volunteer']));

    // An active WeDigBio event over the same days
    WeDigBioEvent::factory()->create(['active' => 1, 'start_date' => '2026-10-01 00:00:00', 'end_date' => '2026-10-10 00:00:00']);
});

afterEach(function () {
    PanoptesTranscription::truncate();
    PusherTranscription::truncate();
    Subject::truncate();
    $this->travelBack();
});

function reconciledTranscription(Expedition $expedition, int $classificationId, string $finishedAt): void
{
    if (Subject::count() === 0) {
        Subject::factory()->create(['project_id' => $expedition->project_id, 'accessURI' => 'https://images.example.org/1.jpg']);
    }

    PanoptesTranscription::create([
        'classification_id' => $classificationId,
        'subject_subjectId' => (string) Subject::sole()->_id,
        'subject_references' => 'https://www.zooniverse.org/subjects/1',
        'subject_accessURI' => 'https://images.example.org/1.jpg',
        'subject_imageURL' => '',
        'subject_expeditionId' => $expedition->id,
        'subject_projectId' => $expedition->project_id,
        'user_name' => 'volunteer',
        'Country' => 'United States',
        'County' => 'Alachua',
        'Location' => 'Gainesville',
        'classification_finished_at' => Carbon::parse($finishedAt, 'UTC'),
    ]);
}

function runPusherJob(Expedition $expedition): void
{
    app()->call([new ZooniversePusherJob($expedition), 'handle']);
}

it('turns the last three days of reconciled transcriptions into Pusher and event records', function () {
    reconciledTranscription($this->expedition, 1001, '2026-10-07 12:00:00');
    reconciledTranscription($this->expedition, 1002, '2026-10-02 12:00:00');

    runPusherJob($this->expedition);

    expect(PusherTranscription::pluck('classification_id')->all())->toBe([1001])
        ->and(EventTranscription::pluck('classification_id')->all())->toBe([1001])
        ->and(WeDigBioEventTranscription::pluck('classification_id')->all())->toBe([1001]);
    Queue::assertPushed(ScoreboardJob::class);
    Queue::assertPushed(WeDigBioEventProgressJob::class);
});

it('records a classification once when the job runs again', function () {
    reconciledTranscription($this->expedition, 1001, '2026-10-07 12:00:00');

    runPusherJob($this->expedition);
    runPusherJob($this->expedition);

    expect(PusherTranscription::where('classification_id', 1001)->count())->toBe(1)
        ->and(EventTranscription::where('classification_id', 1001)->count())->toBe(1)
        ->and(WeDigBioEventTranscription::where('classification_id', 1001)->count())->toBe(1);
});

it('skips expeditions listed in skip_reconcile', function () {
    config(['zooniverse.skip_reconcile' => [$this->expedition->id]]);
    reconciledTranscription($this->expedition, 1001, '2026-10-07 12:00:00');

    runPusherJob($this->expedition);

    expect(PusherTranscription::count())->toBe(0)
        ->and(EventTranscription::count())->toBe(0);
});
