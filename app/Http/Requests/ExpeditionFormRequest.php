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

namespace App\Http\Requests;

use App\Rules\FileUploadNameValidation;
use Illuminate\Support\Facades\Auth;

/**
 * Class ExpeditionFormRequest
 */
class ExpeditionFormRequest extends Request
{
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Validation rules.
     *
     * @return array
     */
    public function rules()
    {
        $expeditionId = isset($this->route('expedition')->id) ? $this->route('expedition')->id : null;

        return [
            'title' => 'required|between:6,140|unique:expeditions,title,'.$expeditionId,
            'description' => 'required|between:6,140',
            'keywords' => 'required',
            'logo' => [
                'image',
                new FileUploadNameValidation,
            ],
            'logo_path' => 'nullable|string',
            'workflow_id' => 'required',
        ];
    }

    /**
     * Alter group form input before validation.
     *
     * @return array
     */
    public function alterInput()
    {
        $input = $this->all();
        $input['title'] = trim($input['title']);
        $this->replace($input);

        return $this->all();
    }
}
