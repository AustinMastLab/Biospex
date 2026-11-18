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

use App\Livewire\Admin\GroupGeoLocateFormsTable;
use App\Models\GeoLocateDataSource;
use App\Models\GeoLocateForm;
use App\Models\Group;
use Livewire\Livewire;

/**
 * A GeoLocateExport form of the group, assigned to the given number of expeditions.
 */
function groupGeoLocateForm(Group $group, string $name, int $expeditions = 0): GeoLocateForm
{
    $form = GeoLocateForm::factory()->create(['group_id' => $group->id, 'name' => $name]);
    GeoLocateDataSource::factory()->count($expeditions)->create(['geo_locate_form_id' => $form->id]);

    return $form;
}

it('lists the group forms with their assigned expeditions and a delete link', function () {
    $group = Group::factory()->create();
    $form = groupGeoLocateForm($group, 'Herbarium Form', expeditions: 1);
    $expedition = $form->expeditions()->first();
    groupGeoLocateForm(Group::factory()->create(), 'Someone Elses Form');

    Livewire::test(GroupGeoLocateFormsTable::class, ['group' => $group])
        ->assertSee('Herbarium Form')
        ->assertSeeHtml('href="'.route('admin.expeditions.show', [$expedition]).'">'.$expedition->id.'</a>')
        ->assertSeeHtml('href="'.route('admin.groups-geolocate-form.destroy', [$group, $form]).'"')
        ->assertDontSee('Someone Elses Form');
});

it('searches form names', function () {
    $group = Group::factory()->create();
    groupGeoLocateForm($group, 'Herbarium Form');
    groupGeoLocateForm($group, 'Field Notes Form');

    Livewire::test(GroupGeoLocateFormsTable::class, ['group' => $group])
        ->set('search', 'field')
        ->assertSee('Field Notes Form')
        ->assertDontSee('Herbarium Form');
});

it('sorts by the number of assigned expeditions', function () {
    $group = Group::factory()->create();
    groupGeoLocateForm($group, 'Busy Form', expeditions: 2);
    groupGeoLocateForm($group, 'Idle Form');

    Livewire::test(GroupGeoLocateFormsTable::class, ['group' => $group])
        ->call('sortBy', 'expeditions_count')
        ->assertSeeInOrder(['Idle Form', 'Busy Form'])
        ->call('sortBy', 'expeditions_count')
        ->assertSeeInOrder(['Busy Form', 'Idle Form']);
});
