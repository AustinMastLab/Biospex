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

use App\Rules\EventJoinUniqueUserTeamValidation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class EventJoinRequest
 */
class EventJoinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nfn_user' => ['required', 'between:3,30', new EventJoinUniqueUserTeamValidation],
        ];
    }

    public function messages()
    {
        return [
            'nfn_user.required' => 'Zooniverse username required',
            'nfn_user.between' => 'Username must be between 3-30 characters',
        ];
    }
}
