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
 * Class SiteAssetPresenter
 */
class SiteAssetPresenter extends Presenter
{
    /**
     * Build asset link with support for a new Livewire path.
     *
     * @return string
     */
    public function assetUrl()
    {
        // Check for new Livewire download_path first (check S3 for new uploads)
        if (! empty($this->model->download_path) && Storage::disk('s3')->exists($this->model->download_path)) {
            $url = Storage::disk('s3')->url($this->model->download_path);
            $filename = basename($this->model->download_path);
            $ariaLabel = e(t('Open file: %s (site asset %s; opens in a new tab)', (string) $filename, (string) $this->model->id));

            return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer" aria-label="'.$ariaLabel.'"><i class="fas fa-file" aria-hidden="true"></i> '.e((string) $filename).'</a>';
        }

        return '';
    }
}
