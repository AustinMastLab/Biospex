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
 * Class BingoPresenter
 */
class BingoPresenter extends Presenter
{
    /**
     * Returns event date as string.
     *
     * @return string
     */
    public function createDateToString()
    {
        return $this->model->created_at->toDayDateTimeString();
    }

    /**
     * Return show icon.
     *
     * @return string
     */
    public function adminShowIcon()
    {
        return '<a href="'.route('admin.bingos.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('View Bingo').'" aria-label="'.t('View Bingo').'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function showIcon()
    {
        return '<a href="'.route('front.bingos.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('View Bingo').'" aria-label="'.t('View Bingo').'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function editIcon()
    {
        return '<a href="'.route('admin.bingos.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('Edit Bingo').'" aria-label="'.t('Edit Bingo').'">
                <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function editIconLrg()
    {
        return '<a href="'.route('admin.bingos.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.t('Edit Bingo').'" aria-label="'.t('Edit Bingo').'">
                <i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function deleteIcon()
    {
        return '<a href="'.route('admin.bingos.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.t('Delete Bingo').'"
            aria-label="'.t('Delete Bingo').'"
            data-hover="tooltip"
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.t('Delete Bingo').'?" data-content="'.t('This will permanently delete the record and all associated records.').'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function deleteIconLrg()
    {
        return '<a href="'.route('admin.bingos.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.t('Delete Bingo').'"
            aria-label="'.t('Delete Bingo').'"
            data-hover="tooltip"
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.t('Delete Bingo').'?" data-content="'.t('This will permanently delete the record and all associated records.').'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    public function twitterIcon()
    {
        $id = $this->model->id;
        $title = $this->model->title;
        $url = config('app.url').'/bingos/'.$id.'&text='.$title;

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
        $url = urlencode(config('app.url').'/bingos/'.$this->model->id);
        $title = urlencode($this->model->title);

        return '<a href="http://www.facebook.com/share.php?u='.$url.'&title='.$title.'"
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip"
            title="'.t('Share on Facebook').'"
            aria-label="'.t('Share on Facebook').'">
            <i class="fab fa-facebook" aria-hidden="true"></i></a>';
    }

    public function contactIcon()
    {
        return '<a href="mailto:'.$this->model->contact.'" data-hover="tooltip" title="'.t('Contact').'" aria-label="'.t('Contact').'">
                <i class="fas fa-envelope" aria-hidden="true"></i></a>';
    }
}
