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
        $label = e(t('View Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('admin.bingos.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('View Bingo')).'" aria-label="'.$label.'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function showIcon()
    {
        $label = e(t('View Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('front.bingos.show', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('View Bingo')).'" aria-label="'.$label.'">
                <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function editIcon()
    {
        $label = e(t('Edit Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('admin.bingos.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('Edit Bingo')).'" aria-label="'.$label.'">
                <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function editIconLrg()
    {
        $label = e(t('Edit Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('admin.bingos.edit', [
            $this->model,
        ]).'" data-hover="tooltip" title="'.e(t('Edit Bingo')).'" aria-label="'.$label.'">
                <i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function deleteIcon()
    {
        $label = e(t('Delete Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('admin.bingos.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete Bingo')).'"
            aria-label="'.$label.'"
            data-hover="tooltip"
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete Bingo')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function deleteIconLrg()
    {
        $label = e(t('Delete Bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="'.route('admin.bingos.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete Bingo')).'"
            aria-label="'.$label.'"
            data-hover="tooltip"
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete Bingo')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    public function twitterIcon()
    {
        $id = $this->model->id;
        $title = $this->model->title;
        $url = config('app.url').'/bingos/'.$id.'&text='.$title;

        $ariaLabel = e(t(
            'Share bingo: %s on Twitter (bingo %s)',
            (string) $this->model->title,
            (string) $this->model->uuid
        ));

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
        $url = urlencode(config('app.url').'/bingos/'.$this->model->id);
        $title = urlencode($this->model->title);

        $ariaLabel = e(t(
            'Share bingo: %s on Facebook (bingo %s)',
            (string) $this->model->title,
            (string) $this->model->uuid
        ));

        return '<a href="http://www.facebook.com/share.php?u='.$url.'&title='.$title.'"
            target="_blank"
            rel="noopener noreferrer"
            data-hover="tooltip"
            title="'.$ariaLabel.'"
            aria-label="'.$ariaLabel.'">
            <i class="fab fa-facebook" aria-hidden="true"></i></a>';
    }

    public function contactIcon()
    {
        if (empty($this->model->contact)) {
            return '';
        }

        $ariaLabel = e(t('Contact bingo (bingo %s)', (string) $this->model->uuid));

        return '<a href="mailto:'.$this->model->contact.'"
            data-hover="tooltip"
            title="'.$ariaLabel.'"
            aria-label="'.$ariaLabel.'">
                <i class="fas fa-envelope" aria-hidden="true"></i></a>';
    }
}
