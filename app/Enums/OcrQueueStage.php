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

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The step an OCR run has reached, stored in `ocr_queues.stage`.
 *
 * The labels are shown to users in the process monitor.
 */
enum OcrQueueStage: int implements HasLabel
{
    /** Queued behind other OCR runs. */
    case Waiting = 0;

    /** BiospexImageFetcher and BiospexOcrProcessor are working through the images. */
    case ProcessingImages = 1;

    public function getLabel(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting for OCR to start',
            self::ProcessingImages => 'Processing Images',
        };
    }
}
