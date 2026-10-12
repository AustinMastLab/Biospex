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
 * Where an actor (Zooniverse or GeoLocate) is in processing an expedition, stored in `actor_expedition.state`.
 */
enum ActorExpeditionState: int implements HasLabel
{
    /** The actor is attached, but nothing has been exported. */
    case NotStarted = 0;

    /** The expedition has been exported to the actor. */
    case Exported = 1;

    /** Results are being collected: Zooniverse CSVs and reconciliation, or GeoLocate stats. */
    case Processing = 2;

    /** The actor has finished with the expedition. */
    case Complete = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Exported => 'Exported',
            self::Processing => 'Processing',
            self::Complete => 'Complete',
        };
    }
}
