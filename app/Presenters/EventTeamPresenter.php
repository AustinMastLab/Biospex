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
    public function teamJoinUrlIcon()
    {
        $url = route('front.events_team_user.create', [$this->model]);

        $teamTitle = (string) $this->model->title;

        $ariaLabel = e(t('Copy invite link for team: %s', $teamTitle));
        $visibleText = e($teamTitle);
        $srText = e(t('Copy invite link for team: %s', $teamTitle));

        return '<button type="button"
                class="btn btn-primary p-2 m-1 clipboard"
                aria-label="'.$ariaLabel.'"
                title="'.e(t('Copy To Clipboard')).'"
                data-hover="tooltip"
                data-clipboard-text="'.e($url).'">
                <i class="fas fa-clipboard align-middle" aria-hidden="true"></i>
                <span class="sr-only">'.$srText.'</span>
                <span class="align-middle">'.$visibleText.'</span>
            </button>';
    }
}
