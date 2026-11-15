<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

use App\Models\Expedition;
use Illuminate\Testing\TestView;

/**
 * Render the GeoLocate export form with the given form data.
 *
 * @param  array<string, mixed>  $form
 */
function renderGeolocateFormFields(array $form): TestView
{
    test()->withViewErrors([]);

    return test()->view('admin.geolocate.partials.form-fields', [
        'expedition' => Expedition::factory()->create(),
        'disabled' => false,
        'form' => array_merge([
            'group_id' => 1,
            'fields' => null,
            'entries' => 0,
            'name' => '',
            'source' => null,
            'expert_review' => false,
            'expert_reconciled' => false,
            'user_reconciled' => false,
            'geo' => ['County*', 'Country*'],
            'csv' => ['county', 'country'],
        ], $form),
    ]);
}

it('offers only the CSV sources that exist for the expedition', function () {
    $view = renderGeolocateFormFields(['user_reconciled' => true]);

    $view->assertSee('value="reconciled"', false)
        ->assertSee('value="reconciled-with-user"', false)
        ->assertSee('value="upload"', false)
        ->assertDontSee('value="reconciled-with-expert"', false);
});

it('offers the expert source once the expert review is reconciled and published', function () {
    $view = renderGeolocateFormFields(['expert_review' => true, 'expert_reconciled' => true]);

    $view->assertSee('value="reconciled-with-expert"', false)
        ->assertDontSee('value="reconciled-with-user"', false);
});

it('asks for a CSV source until one is chosen', function () {
    $view = renderGeolocateFormFields([]);

    $view->assertSee('<option value="" disabled hidden selected>', false);
});

it('keeps the saved CSV source selected', function () {
    $view = renderGeolocateFormFields(['source' => 'reconciled-with-user', 'user_reconciled' => true]);

    $view->assertSee('<option value="" disabled hidden >', false)
        ->assertSeeInOrder(['value="reconciled-with-user"', 'selected'], false);
});
