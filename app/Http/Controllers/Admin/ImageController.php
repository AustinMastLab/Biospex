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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Asset\Thumbnail;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Request;

/**
 * Class ImageController
 */
class ImageController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct(protected Thumbnail $thumbnail
    ) {}

    /**
     * The subject grid's preview dialogs: the image URL as text (url-view), or a thumbnail that scales to the dialog.
     *
     * @throws FileNotFoundException
     */
    public function preview(): string
    {
        $url = (string) Request::input('url');

        if (Request::has('url-view')) {
            // Escaped, and wrapping anywhere so a long URL stays inside the dialog on a phone.
            return '<p class="text-left px-3 mb-0 user-select-all" style="overflow-wrap: anywhere;">'.e($url).'</p>';
        }

        $thumb = $this->thumbnail->getThumbnail(urldecode($url));

        return '<img class="img-fluid" src="data:image/jpeg;base64,'.base64_encode($thumb).'" alt="'.e(t('Subject image thumbnail')).'">';
    }
}
