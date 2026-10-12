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

namespace App\Events;

use App\Models\Bingo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class BingoEvent
 */
class BingoEvent implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, SerializesModels;

    private Bingo $bingo;

    public string $data;

    /**
     * BingoEvent constructor.
     */
    public function __construct(Bingo $bingo, string $data)
    {
        $this->bingo = $bingo->withoutRelations();
        $this->data = $data;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel(config('config.poll_bingo_channel').'.'.$this->bingo->uuid);
        // return new PresenceChannel(config('config.poll_bingo_channel').'.'.$this->bingo->uuid);
    }
}
