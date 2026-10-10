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

namespace App\Console\Commands;

use App\Enums\ActorExpeditionState;
use App\Models\Expedition;
use Artisan;
use Illuminate\Console\Command;

class CheckExpeditionCompleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expedition-complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get expeditions where relation stats. percent_complete is 100
        $expeditions = Expedition::with('actorExpeditions')->whereHas('stat', function ($query) {
            $query->where('percent_completed', 100);
        })->get();

        $expeditions->each(function ($expedition) {
            $expedition->actorExpeditions->each(function ($actorExpedition) {
                $actorExpedition->state = ActorExpeditionState::Processing;
                $actorExpedition->save();
                Artisan::call('workflow:manage', ['expeditionId' => $actorExpedition->expedition_id]);
            });
        });

    }
}
