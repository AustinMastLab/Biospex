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

use App\Models\Event;
use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::factory()->create(['user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['group_id' => $this->group->id]);

    // Create admin group and assign to user
    $adminGroup = Group::factory()->create(['title' => config('config.admin.group', 'Admin')]);
    $this->user->assignGroup($adminGroup);

    $this->actingAs($this->user);
});

describe('Admin Event Controller Tests', function () {

    describe('Event Create', function () {

        it('displays the event create page', function () {
            $response = $this->get(route('admin.events.create'));

            $response->assertStatus(200)
                ->assertViewIs('admin.event.create')
                ->assertViewHas('projects')
                ->assertSee('Create Event');
        });

        it('can create a new event with teams', function () {
            $eventData = [
                'project_id' => $this->project->id,
                'title' => 'Test Event',
                'description' => 'Test event description',
                'contact' => 'John Doe',
                'contact_email' => 'john@example.com',
                'hashtag' => 'test,event',
                'start_date' => '2025-12-01 10:00',
                'end_date' => '2025-12-31 18:00',
                'timezone' => 'America/New_York',
                'owner_id' => $this->user->id,
                'teams' => [
                    ['id' => null, 'title' => 'Team Alpha'],
                    ['id' => null, 'title' => 'Team Beta'],
                ],
            ];

            $response = $this->post(route('admin.events.store'), $eventData);

            $response->assertRedirect()
                ->assertSessionHas('success', t('Record was created successfully.'));

            $this->assertDatabaseHas('events', [
                'title' => 'Test Event',
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
            ]);

            $event = Event::where('title', 'Test Event')->first();
            $this->assertCount(2, $event->teams);
            $this->assertEquals('Team Alpha', $event->teams->first()->title);
            $this->assertEquals('Team Beta', $event->teams->last()->title);
        });

        it('validates required fields when creating event', function () {
            $response = $this->post(route('admin.events.store'), []);

            $response->assertSessionHasErrors([
                'project_id',
                'title',
                'description',
                'contact',
                'contact_email',
                'start_date',
                'end_date',
                'timezone',
            ]);
        });

        it('validates email format for contact_email', function () {
            $eventData = [
                'project_id' => $this->project->id,
                'title' => 'Test Event',
                'description' => 'Test description',
                'contact' => 'John Doe',
                'contact_email' => 'invalid-email',
                'start_date' => '2025-12-01 10:00',
                'end_date' => '2025-12-31 18:00',
                'timezone' => 'America/New_York',
                'owner_id' => $this->user->id,
                'teams' => [['id' => null, 'title' => 'Team Alpha']],
            ];

            $response = $this->post(route('admin.events.store'), $eventData);

            $response->assertSessionHasErrors(['contact_email']);
        });
    });

    describe('Event Edit', function () {

        it('displays the event edit page', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
            ]);

            $response = $this->get(route('admin.events.edit', $event));

            $response->assertStatus(200)
                ->assertViewIs('admin.event.edit')
                ->assertViewHas(['event', 'projects'])
                ->assertSee('Edit Event')
                ->assertSee($event->title);
        });

        it('can update an existing event', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
                'title' => 'Original Title',
            ]);

            $updateData = [
                'project_id' => $event->project_id,
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'contact' => 'Jane Doe',
                'contact_email' => 'jane@example.com',
                'hashtag' => 'updated,tags',
                'start_date' => $event->start_date->format('Y-m-d H:i'),
                'end_date' => $event->end_date->format('Y-m-d H:i'),
                'timezone' => $event->timezone,
                'owner_id' => $event->owner_id,
                'teams' => [
                    ['id' => null, 'title' => 'New Team'],
                ],
            ];

            $response = $this->put(route('admin.events.update', $event), $updateData);

            $response->assertRedirect()
                ->assertSessionHas('success', t('Record was updated successfully.'));

            $event->refresh();
            $this->assertEquals('Updated Title', $event->title);
            $this->assertEquals('Updated description', $event->description);
            $this->assertEquals('Jane Doe', $event->contact);
            $this->assertEquals('jane@example.com', $event->contact_email);
        });

        it('redirects unauthorized users trying to edit event', function () {
            // Create a non-admin user to test authorization
            $nonAdminUser = User::factory()->create(['email_verified_at' => now()]);
            $this->actingAs($nonAdminUser);

            $otherUser = User::factory()->create();
            $otherGroup = Group::factory()->create(['user_id' => $otherUser->id]);
            $event = Event::factory()->create([
                'project_id' => Project::factory()->create(['group_id' => $otherGroup->id])->id,
                'owner_id' => $otherUser->id,
            ]);

            $response = $this->get(route('admin.events.edit', $event));

            $response->assertRedirect();
        });
    });

    describe('Event View', function () {

        it('displays the event show page', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
            ]);

            $response = $this->get(route('admin.events.show', $event));

            $response->assertStatus(200)
                ->assertViewIs('admin.event.show')
                ->assertViewHas('event')
                ->assertSee($event->title)
                ->assertSee($event->description);
        });

        it('redirects unauthorized users trying to view event', function () {
            // Create a non-admin user to test authorization
            $nonAdminUser = User::factory()->create(['email_verified_at' => now()]);
            $this->actingAs($nonAdminUser);

            $otherUser = User::factory()->create();
            $otherGroup = Group::factory()->create(['user_id' => $otherUser->id]);
            $event = Event::factory()->create([
                'project_id' => Project::factory()->create(['group_id' => $otherGroup->id])->id,
                'owner_id' => $otherUser->id,
            ]);

            $response = $this->get(route('admin.events.show', $event));

            $response->assertRedirect(route('admin.events.index'));
        });

        it('loads event with proper relationships for show page', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
            ]);

            $response = $this->get(route('admin.events.show', $event));

            $response->assertStatus(200);

            // Verify the event in the view has the expected loaded relationships
            $viewEvent = $response->viewData('event');
            $this->assertTrue($viewEvent->relationLoaded('project'));
            $this->assertTrue($viewEvent->relationLoaded('teams'));
        });

        it('names the project\'s Zooniverse project in its link on the show page', function () {
            PanoptesProject::factory()->create([
                'project_id' => $this->project->id,
                'panoptes_workflow_id' => 12345,
                'title' => 'Ferns of Florida',
            ]);
            // The Zooniverse link shows while the event is running.
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDay(),
            ]);

            $this->get(route('admin.events.show', $event))
                ->assertOk()
                ->assertSee('View Ferns of Florida on Zooniverse', false);
        });
    });

    describe('Event Index', function () {

        it('displays the events index page', function () {
            Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
                'title' => 'Test Event',
            ]);

            $response = $this->get(route('admin.events.index'));

            $response->assertStatus(200)
                ->assertViewIs('admin.event.index')
                ->assertViewMissing(['events', 'eventsCompleted'])
                ->assertSeeLivewire('admin.events-index')
                ->assertSeeInOrder([
                    t('view completed events'),
                    t('New Event'),
                ])
                ->assertSee('Test Event');
        });
    });

    describe('Event Delete', function () {

        it('can delete an event', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
            ]);

            $response = $this->delete(route('admin.events.destroy', $event));

            $response->assertRedirect(route('admin.events.index'))
                ->assertSessionHas('success', t('Record has been scheduled for deletion and changes will take effect in a few minutes.'));
        });

        it('prevents unauthorized users from deleting events', function () {
            // Create a non-admin user to test authorization
            $nonAdminUser = User::factory()->create(['email_verified_at' => now()]);
            $this->actingAs($nonAdminUser);

            $otherUser = User::factory()->create();
            $otherGroup = Group::factory()->create(['user_id' => $otherUser->id]);
            $event = Event::factory()->create([
                'project_id' => Project::factory()->create(['group_id' => $otherGroup->id])->id,
                'owner_id' => $otherUser->id,
            ]);

            $response = $this->delete(route('admin.events.destroy', $event));

            $response->assertRedirect(route('admin.events.index'));
        });
    });

    describe('Event Dates', function () {

        /**
         * Event form data with each date sent as a date field and a time from the list.
         *
         * @param  array<string, mixed>  $overrides
         * @return array<string, mixed>
         */
        function eventDateFormData(array $overrides = []): array
        {
            return array_merge([
                'project_id' => test()->project->id,
                'title' => 'Date Field Event',
                'description' => 'Event with split date fields',
                'contact' => 'John Doe',
                'contact_email' => 'john@example.com',
                'start_date_date' => '2025-12-01',
                'start_date_time' => '10:00',
                'end_date_date' => '2025-12-31',
                'end_date_time' => '18:30',
                'timezone' => 'America/New_York',
                'owner_id' => test()->user->id,
                'teams' => [['id' => null, 'title' => 'Team Alpha']],
            ], $overrides);
        }

        it('saves the date field and time in the event time zone', function () {
            $response = $this->post(route('admin.events.store'), eventDateFormData());

            $response->assertRedirect();
            $event = Event::where('title', 'Date Field Event')->firstOrFail();
            expect($event->getRawOriginal('start_date'))->toBe('2025-12-01 15:00:00')
                ->and($event->getRawOriginal('end_date'))->toBe('2025-12-31 23:30:00');
        });

        it('rejects a date without a time', function () {
            $response = $this->post(route('admin.events.store'), eventDateFormData(['start_date_time' => '']));

            $response->assertSessionHasErrors(['start_date']);
            $this->assertDatabaseMissing('events', ['title' => 'Date Field Event']);
        });

        it('shows a saved time that is not on the half hour in the edit form', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
                'timezone' => 'America/New_York',
                'start_date' => Carbon\Carbon::parse('2022-01-27 01:00:00', 'UTC'),
                'end_date' => Carbon\Carbon::parse('2022-04-21 12:39:00', 'UTC'),
            ]);

            $response = $this->get(route('admin.events.edit', $event));

            $response->assertOk()
                ->assertSee('name="start_date_date"', false)
                ->assertSee('value="2022-01-26"', false)
                ->assertSee('<option value="20:00" selected>', false)
                ->assertSee('value="2022-04-21"', false)
                ->assertSee('<option value="08:39" selected>', false);
        });

        it('keeps the stored dates when an event is saved with the dates the edit form shows', function () {
            $event = Event::factory()->create([
                'project_id' => $this->project->id,
                'owner_id' => $this->user->id,
                'timezone' => 'America/Chicago',
                'start_date' => Carbon\Carbon::parse('2019-10-31 08:11:00', 'UTC'),
                'end_date' => Carbon\Carbon::parse('2019-11-08 23:30:00', 'UTC'),
            ]);

            $response = $this->put(route('admin.events.update', $event), eventDateFormData([
                'title' => $event->title,
                'timezone' => 'America/Chicago',
                'start_date_date' => '2019-10-31',
                'start_date_time' => '03:11',
                'end_date_date' => '2019-11-08',
                'end_date_time' => '17:30',
                'teams' => [],
            ]));

            $response->assertRedirect();
            $event->refresh();
            expect($event->getRawOriginal('start_date'))->toBe('2019-10-31 08:11:00')
                ->and($event->getRawOriginal('end_date'))->toBe('2019-11-08 23:30:00');
        });
    });
});
