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
use App\Models\OcrQueue;
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
