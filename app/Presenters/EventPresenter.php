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

use Carbon\Carbon;
use DateTimeZone;

/**
 * Class EventPresenter
 */
class EventPresenter extends Presenter
{
    /**
     * Returns start date according to timezone.
     *
     * @return mixed
     */
    public function startDateTimezone()
    {
        return $this->model->start_date->setTimezone($this->model->timezone);
    }

    /**
     * Return start date formatted for calender picker.
     *
     * @return mixed
     */
    public function startDateCalendar()
    {
        return $this->startDateTimezone()->format('Y-m-d H:i');
    }

    /**
     * Returns event date as string.
     *
     * @return string
     */
    public function startDateToString()
    {
        return $this->startDateTimezone()->toDayDateTimeString();
    }

    /**
     * Returns end date according to timezone.
     *
     * @return mixed
     */
    public function endDateTimezone()
    {
        return $this->model->end_date->setTimezone($this->model->timezone);
    }

    /**
     * Returns event date as string.
     *
     * @return string
     */
    public function endDateCalendar()
    {
        return $this->endDateTimezone()->format('Y-m-d H:i');
    }

    /**
     * Returns event date as string.
     *
     * @return string
     */
    public function endDateToString()
    {
        return $this->endDateTimezone()->toDayDateTimeString();
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
    public function scoreboardDate()
    {
        $now = Carbon::now(new DateTimeZone('UTC'));
        $start_date = $this->model->start_date->setTimezone('UTC');
        $end_date = $this->model->end_date->setTimeZone('UTC');

        if ($now->gt($end_date)) {
            return 'Completed';
        }

        return $end_date->gt($start_date) ? $end_date->toIso8601ZuluString() : $start_date->toIso8601ZuluString();
    }

    /**
     * Create Twitter icon.
     *
     * <a href="https://twitter.com/intent/tweet?url=https%3A%2F%2Fbiospex.org%2Fevents%2F13&text=Event%20to%20show&hashtags=biospex%2Ceventname" target="_blank">
     * <i class="fab fa-twitter"></i> <span class="d-none text d-sm-inline"></span>
     * </a>
     *
     * @return string
     */
    public function twitterIcon()
    {
        $uuid = $this->model->uuid;
        $title = $this->model->title;
        $hashtag = $this->model->hashtag;
        $url = config('app.url').'/events/'.$uuid.'&text='.$title.'&hashtags='.$hashtag;

        $ariaLabel = e(t('Share event: %s on Twitter (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="https://twitter.com/intent/tweet?url='.$url.'" 
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip" 
            title="'.$ariaLabel.'"
            aria-label="'.$ariaLabel.'">
            <i class="fab fa-twitter" aria-hidden="true"></i></a>';
    }

    public function facebookIcon()
    {
        $url = urlencode(config('app.url').'/events/'.$this->model->uuid);
        $title = urlencode($this->model->title);

        $ariaLabel = e(t('Share event: %s on Facebook (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="http://www.facebook.com/share.php?u='.$url.'&title='.$title.'" 
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip" 
            title="'.$ariaLabel.'"
            aria-label="'.$ariaLabel.'">
            <i class="fab fa-facebook" aria-hidden="true"></i></a>';
    }

    public function contactEmailIcon()
    {
        if ($this->model->contact_email === null) {
            return '';
        }

        $ariaLabel = e(t('Contact event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="mailto:'.$this->model->contact_email.'" 
            data-hover="tooltip" 
            title="'.e(t('Contact event')).'"
            aria-label="'.$ariaLabel.'">
            <i class="far fa-envelope" aria-hidden="true"></i></a>';
    }

    public function eventShowIcon()
    {
        $ariaLabel = e(t('View event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('front.events.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('View event')).'" aria-label="'.$ariaLabel.'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function eventAdminShowIcon()
    {
        $ariaLabel = e(t('View event (Admin): %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.events.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('View event (Admin)')).'" aria-label="'.$ariaLabel.'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function eventEditIcon()
    {
        $ariaLabel = e(t('Edit event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.events.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('Edit event')).'" aria-label="'.$ariaLabel.'">
                <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function eventEditIconLrg()
    {
        $ariaLabel = e(t('Edit event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.events.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('Edit event')).'" aria-label="'.$ariaLabel.'"><i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDeleteIcon()
    {
        $ariaLabel = e(t('Delete event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.events.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete event')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete event')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function eventDeleteIconLrg()
    {
        $ariaLabel = e(t('Delete event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.events.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete event')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete event')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDownloadUsersIconLrg()
    {
        $route = route('admin.events_users.index', [
            $this->model,
        ]);

        $ariaLabel = e(t('Download participants file for event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="#" class="prevent-default event-export"
            aria-label="'.$ariaLabel.'"
            data-href="'.$route.'"
            data-success="'.e(t('An email with attached export will be sent.')).'"
            data-error="'.e(t('There was an error while exporting. Please inform the Administration')).'"
            data-hover="tooltip" title="'.e(t('Download participants file')).'"><i class="fas fa-users fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDownloadDigitizationsIconLrg()
    {
        $route = route('admin.events_transcriptions.index', [
            $this->model,
        ]);

        $ariaLabel = e(t('Download digitizations file for event: %s (event %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="#" class="prevent-default event-export"
            aria-label="'.$ariaLabel.'"
            data-href="'.$route.'"
            data-success="'.e(t('An email with attached export will be sent.')).'"
            data-error="'.e(t('There was an error while exporting. Please inform the Administration')).'"
            data-hover="tooltip" title="'.e(t('Download digitizations file')).'">
            <i class="fas fa-file-download fa-2x" aria-hidden="true"></i></a>';
    }
}
