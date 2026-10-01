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

namespace App\Presenters;

use Illuminate\Support\Carbon;

/**
 * Class WeDigBioDatePresenter
 */
class WeDigBioDatePresenter extends Presenter
{
    public function progressTitle()
    {
        return t('WEDIGBIO').' '.$this->model->start_date->isoFormat('MMMM YYYY');
    }

    /**
     * Return date for scoreboard.
     *
     * start_date count down
     * event end date count down
     * after end date completed
     *
     * @return string
     */
    public function progressDate()
    {
        $now = Carbon::now('UTC');
        $start_date = $this->model->start_date->setTimezone('UTC');
        $end_date = $this->model->end_date->setTimeZone('UTC');

        if ($now->lt($start_date)) {
            return $start_date->toIso8601ZuluString();
        }

        if ($now->gt($end_date)) {
            return 'Completed';
        }

        return $end_date->toIso8601ZuluString();
    }

    /**
     * Returns start date in day string format.
     *
     * @return mixed
     */
    public function startDateToString()
    {
        return $this->model->start_date->toDayDateTimeString();
    }

    /**
     * Returns start date in day string format.
     *
     * @return mixed
     */
    public function endDateToString()
    {
        return $this->model->end_date->toDayDateTimeString();
    }
}
