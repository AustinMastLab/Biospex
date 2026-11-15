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

use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class EventFormRequest
 */
class EventFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * The form sends each date as a date field and a time list ({field}_date and {field}_time);
     * combine them into the "Y-m-d H:i" value the rules and EventService expect.
     */
    protected function prepareForValidation(): void
    {
        foreach (['start_date', 'end_date'] as $field) {
            if ($this->has($field.'_date') || $this->has($field.'_time')) {
                $this->merge([$field => trim($this->input($field.'_date').' '.$this->input($field.'_time'))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $eventId = isset($this->route('event')->id) ? $this->route('event')->id : null;

        return [
            'project_id' => 'required',
            'title' => 'required|between:6,140|unique:expeditions,title,'.$eventId,
            'description' => 'required|between:6,140',
            'contact' => 'required',
            'contact_email' => 'required|email',
            'start_date' => 'required|date_format:Y-m-d H:i|before:end_date',
            'end_date' => 'required|date_format:Y-m-d H:i|after:start_date',
            'timezone' => 'required|string',
            'teams.*.title' => 'max:20',
        ];
    }

    public function messages()
    {
        return [
            'start_date.before' => 'Date must be greater than End',
            'end_date.after' => 'Date must be greater than Start',
            'teams.*.title.max' => 'Title is 20 character max',
        ];
    }
}
