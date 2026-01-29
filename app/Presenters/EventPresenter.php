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

        return '<a href="https://twitter.com/intent/tweet?url='.$url.'" 
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip" 
            title="'.t('Share on Twitter').'"
            aria-label="'.t('Share on Twitter').'">
            <i class="fab fa-twitter" aria-hidden="true"></i></a>';
    }

    public function facebookIcon()
    {
        $url = urlencode(config('app.url').'/events/'.$this->model->uuid);
        $title = urlencode($this->model->title);

        return '<a href="http://www.facebook.com/share.php?u='.$url.'&title='.$title.'" 
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip" 
            title="'.t('Share on Facebook').'"
            aria-label="'.t('Share on Facebook').'">
            <i class="fab fa-facebook" aria-hidden="true"></i></a>';
    }

    public function contactEmailIcon()
    {
        return $this->model->contact_email === null ? '' :
            '<a href="mailto:'.$this->model->contact_email.'" 
            data-hover="tooltip" 
            title="'.t('Contact').'"
            aria-label="'.t('Contact').'">
            <i class="far fa-envelope" aria-hidden="true"></i></a>';
    }

    public function eventShowIcon()
    {
        return '<a href="'.route('front.events.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('View Event').'" aria-label="'.t('View Event').'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function eventAdminShowIcon()
    {
        return '<a href="'.route('admin.events.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('View Event').'" aria-label="'.t('View Event').'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function eventEditIcon()
    {
        return '<a href="'.route('admin.events.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('Edit Event').'" aria-label="'.t('Edit Event').'">
                <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function eventEditIconLrg()
    {
        return '<a href="'.route('admin.events.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('Edit Event').'" aria-label="'.t('Edit Event').'"><i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDeleteIcon()
    {
        return '<a href="'.route('admin.events.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.t('Delete Event').'"
            aria-label="'.t('Delete Event').'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.t('Delete Event').'?" data-content="'.t('This will permanently delete the record and all associated records.').'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function eventDeleteIconLrg()
    {
        return '<a href="'.route('admin.events.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.t('Delete Event').'"
            aria-label="'.t('Delete Event').'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.t('Delete Event').'?" data-content="'.t('This will permanently delete the record and all associated records.').'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDownloadUsersIconLrg()
    {
        $route = route('admin.events_users.index', [
            $this->model,
        ]);

        return '<a href="#" class="prevent-default event-export"
        aria-label="'.t('Download Participants File').'"
        data-href="'.$route.'"
        data-success="An email with attached export will be sent."
        data-error="There was an error while exporting. Please inform the Administration"
        data-hover="tooltip" title="'.t('Download Participants File').'"><i class="fas fa-users fa-2x" aria-hidden="true"></i></a>';
    }

    public function eventDownloadDigitizationsIconLrg()
    {
        $route = route('admin.events_transcriptions.index', [
            $this->model,
        ]);

        return '<a href="#" class="prevent-default event-export"
        aria-label="'.t('Download Digitizations File').'"
        data-href="'.$route.'"
        data-success="An email with attached export will be sent."
        data-error="There was an error while exporting. Please inform the Administration"
        data-hover="tooltip" title="'.t('Download Digitizations File').'">
        <i class="fas fa-file-download fa-2x" aria-hidden="true"></i></a>';
    }
}
