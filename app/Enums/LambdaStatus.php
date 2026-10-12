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

/**
 * The `status` a Lambda sets in its SQS result message.
 *
 * Queued jobs keep the plain string from the message, so compare with `LambdaStatus::tryFrom($status)`.
 */
enum LambdaStatus: string
{
    /** An image, OCR, batch or reconcile step succeeded. */
    case Success = 'success';

    /** An image, OCR, batch or reconcile step failed; also set by image:listen-dlq. */
    case Failed = 'failed';

    /** BiospexZipCreator or BiospexZipMerger finished the export zip. */
    case ZipReady = 'zip-ready';

    /** BiospexZipCreator finished one part of a large export; BiospexZipMerger reports the whole zip. */
    case PartialZipReady = 'partial-zip-ready';

    /** BiospexZipCreator or BiospexZipMerger failed. */
    case ZipFailed = 'zip-failed';
}
