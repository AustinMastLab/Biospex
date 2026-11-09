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

use App\Enums\OcrQueueStage;
use App\Models\Group;
use App\Models\OcrQueue;
use App\Models\OcrQueueFile;
use App\Models\User;

/**
 * Sign in as a verified user in the admin group, with a group of their own.
 *
 * Pages are visited by path: route() builds absolute URLs from APP_URL, which would leave the test server.
 */
function signInAsAdmin(): User
{
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $admin->assignGroup(Group::factory()->create(['user_id' => $admin->id]));
    test()->actingAs($admin);

    return $admin;
}

/**
 * Whether the modal's <dialog> is open, read in the page.
 */
function modalIsOpen(string $id): string
{
    return "document.getElementById('{$id}').open";
}

it('updates the Processes progress bar while the modal is open, and stops when it is closed', function () {
    signInAsAdmin();
    $queue = OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'total' => 4, 'error' => 0]);
    $files = OcrQueueFile::factory()->count(3)->create(['queue_id' => $queue->id, 'processed' => 0]);
    $files[0]->update(['processed' => 1]);

    // Any admin page has the menu; this one doesn't list the queue's project (whose logo would need S3).
    $page = visit('/admin/projects/create');
    // The trigger sits in a closed dropdown menu, so it is clicked from the page.
    $page->script("document.querySelector('[data-modal-target=\"#process-modal\"]').click()");

    $page->assertScript(modalIsOpen('process-modal'), true)
        ->assertSeeIn('#process-modal', '1 / 4');

    $files[1]->update(['processed' => 1]);
    $page->wait(6);

    $page->assertSeeIn('#process-modal', '2 / 4')
        ->assertSeeIn('#process-modal', '50%');

    $page->click('#process-modal .modal-footer [data-modal-dismiss]');
    $page->wait(0.5);
    $files[2]->update(['processed' => 1]);
    $page->wait(6);

    $page->assertScript(modalIsOpen('process-modal'), false)
        ->assertScript("document.querySelector('#process-modal .modal-body').textContent.includes('2 / 4')", true)
        ->assertNoJavaScriptErrors();
});

it('closes a modal with the close button, Escape and the backdrop, and reopens it', function () {
    signInAsAdmin();

    $page = visit('/admin/projects');
    $open = "document.querySelector('[data-modal-target=\"#process-modal\"]').click()";

    $page->script($open);
    $page->assertScript(modalIsOpen('process-modal'), true)
        ->assertScript("document.body.classList.contains('modal-open')", true);
    $page->click('#process-modal .close');
    $page->wait(0.5);
    $page->assertScript(modalIsOpen('process-modal'), false)
        ->assertScript("document.body.classList.contains('modal-open')", false);

    $page->script($open);
    $page->keys('#process-modal .close', 'Escape');
    $page->wait(0.5);
    $page->assertScript(modalIsOpen('process-modal'), false);

    $page->script($open);
    // A click outside .modal-content lands on the <dialog> itself: the backdrop.
    $page->script("document.getElementById('process-modal').dispatchEvent(new MouseEvent('click', {bubbles: true}))");
    $page->wait(0.5);
    $page->assertScript(modalIsOpen('process-modal'), false);

    $page->script($open);
    $page->assertScript(modalIsOpen('process-modal'), true)
        ->assertNoJavaScriptErrors();
});

it('picks a project banner and closes the banner modal', function () {
    signInAsAdmin();

    $page = visit('/admin/projects/create');
    $page->click('[data-modal-target="#project-banner-modal"]');

    $page->assertScript(modalIsOpen('project-banner-modal'), true)
        ->assertCount('#project-banner-modal .project-banner', 6);

    $page->click('#project-banner-modal [data-name="banner-meadow.jpg"]');
    $page->wait(0.5);

    $page->assertScript(modalIsOpen('project-banner-modal'), false)
        ->assertValue('#banner-file', 'banner-meadow.jpg')
        ->assertAttributeContains('#banner-img', 'src', 'banner-meadow.jpg')
        ->assertNoJavaScriptErrors();
});

it('opens the OCR modal with the text sent from the subject grid', function () {
    signInAsAdmin();

    $page = visit('/admin/projects');
    $page->script("Livewire.dispatch('openOcrModal', {cellContent: 'Quercus alba, Leon County'})");
    $page->wait(1);

    $page->assertScript(modalIsOpen('ocr-modal'), true)
        ->assertSeeIn('#ocr-modal', 'Quercus alba, Leon County')
        ->assertNoJavaScriptErrors();
});
