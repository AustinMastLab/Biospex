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

use App\Models\City;
use App\Services\Csv\Csv;
use Illuminate\Console\Command;
use League\Csv\Exception;

class PopulateCitiesTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-cities-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads storage/worldcities.csv into the cities table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(protected Csv $csv)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $this->csv->readerCreateFromPath(storage_path('worldcities.csv'));
        $this->csv->setHeaderOffset();
        $rows = $this->csv->getRecords();
        foreach ($rows as $row) {
            City::create([
                'city' => $row['city'],
                'latitude' => $row['lat'],
                'longitude' => $row['lng'],
            ]);
        }
        echo "Cities table populated successfully.\n";
    }
}
