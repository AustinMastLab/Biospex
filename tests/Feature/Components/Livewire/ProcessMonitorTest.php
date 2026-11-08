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

use App\Enums\ExportQueueStage;
use App\Enums\OcrQueueStage;
use App\Livewire\ProcessMonitor;
use App\Models\ExportQueue;
use App\Models\ExportQueueFile;
use App\Models\OcrQueue;
use App\Models\OcrQueueFile;
use Livewire\Livewire;

it('shows users the stage each OCR run and export has reached', function () {
    OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'error' => 0]);
    ExportQueue::factory()->create(['stage' => ExportQueueStage::BuildingCsv, 'queued' => 1, 'error' => 0]);

    Livewire::test(ProcessMonitor::class)
        ->assertSee('Processing Images')
        ->assertSee('Building CSV');
});

it('shows an export that has not started as waiting', function () {
    ExportQueue::factory()->create(['stage' => ExportQueueStage::Waiting, 'queued' => 0, 'error' => 0]);

    Livewire::test(ProcessMonitor::class)
        ->assertSee('Waiting for Export to start');
});

it('shows a progress bar with the processed count and percentage for the running OCR', function () {
    $queue = OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'total' => 200, 'error' => 0]);
    OcrQueueFile::factory()->count(50)->create(['queue_id' => $queue->id, 'processed' => 1]);
    OcrQueueFile::factory()->count(10)->create(['queue_id' => $queue->id, 'processed' => 0]);

    Livewire::test(ProcessMonitor::class)
        ->assertSeeHtml('style="width: 25%;')
        ->assertSee('50 / 200')
        ->assertSee('25%');
});

it('shows a progress bar for a running export', function () {
    $queue = ExportQueue::factory()->create(['stage' => ExportQueueStage::BuildingCsv, 'queued' => 1, 'total' => 1500, 'error' => 0]);
    ExportQueueFile::factory()->count(6)->for($queue, 'queue')->create(['processed' => 1]);

    Livewire::test(ProcessMonitor::class)
        ->assertSee('6 / 1,500')
        ->assertSeeHtml('style="width: 0%;');
});

it('shows only the first OCR run as running and the rest by queue position', function () {
    OcrQueue::factory()->count(3)->create(['stage' => OcrQueueStage::Waiting, 'total' => 10, 'error' => 0]);

    Livewire::test(ProcessMonitor::class)
        ->assertSeeHtml('role="progressbar"')
        ->assertSee('Next in queue.')
        ->assertSee('2 processes remain...');
});

it('moves the progress bar forward on refresh', function () {
    $queue = OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'total' => 4, 'error' => 0]);
    OcrQueueFile::factory()->create(['queue_id' => $queue->id, 'processed' => 1]);
    $waiting = OcrQueueFile::factory()->create(['queue_id' => $queue->id, 'processed' => 0]);
    $component = Livewire::test(ProcessMonitor::class)->assertSee('1 / 4');

    $waiting->update(['processed' => 1]);
    $component->call('refreshData');

    $component->assertSee('2 / 4')
        ->assertSee('50%');
});

it('shows 0% instead of failing when a run has no files yet', function () {
    OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'total' => 0, 'error' => 0]);

    Livewire::test(ProcessMonitor::class)
        ->assertSee('0 / 0')
        ->assertSeeHtml('style="width: 0%;');
});

it('leaves out OCR runs and exports that failed', function () {
    OcrQueue::factory()->create(['stage' => OcrQueueStage::ProcessingImages, 'error' => 1]);
    ExportQueue::factory()->create(['stage' => ExportQueueStage::BuildingCsv, 'queued' => 1, 'error' => 1]);

    Livewire::test(ProcessMonitor::class)
        ->assertSee('No OCR processes running')
        ->assertSee('No exports running');
});
