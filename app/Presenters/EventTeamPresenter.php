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

/**
 * Class EventTeamPresenter
 */
class EventTeamPresenter extends Presenter
{
    /**
     * Return return copy icon.
     *
     * @return string
     */
    public function teamJoinUrlIcon()
    {
        return '<a href="'.route('front.events_team_user.create', [$this->model]).'" 
            class="btn btn-primary p-2 m-1 prevent-default clipboard"
            title="'.t('Copy To Clipboard').'" 
            data-hover="tooltip"
            data-clipboard-text="'.route('front.events_team_user.create', [$this->model]).'">
            <i class="fas fa-clipboard align-middle" aria-hidden="true"></i>
            <span class="align-middle">'.$this->model->title.'</span></a>';
    }
}
