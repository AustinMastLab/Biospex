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

use Storage;

/**
 * Class ProjectAssetPresenter
 */
class ProjectAssetPresenter extends Presenter
{
    /**
     * Build site-asset link with support for new Livewire path.
     */
    public function asset(): string
    {
        $name = $this->model->name;
        $description = $this->model->description;

        if ($this->model->type === 'File Download') {
            $url = null;

            // Check for new Livewire download_path first (check S3 for new uploads)
            if (! empty($this->model->download_path) && Storage::disk('s3')->exists($this->model->download_path)) {
                $url = Storage::disk('s3')->url($this->model->download_path);

                return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.$description.'">
                <i class="fas fa-file" aria-hidden="true"></i> '.$name.'</a>';
            }
        }

        return '<a href="'.$name.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.$description.'">
            <i class="fas fa-link" aria-hidden="true"></i> '.$name.'</a>';
    }
}
