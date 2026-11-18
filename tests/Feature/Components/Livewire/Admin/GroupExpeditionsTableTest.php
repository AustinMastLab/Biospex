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

use App\Livewire\Admin\GroupExpeditionsTable;
use App\Models\Expedition;
use App\Models\Group;
use App\Models\Project;
use Livewire\Livewire;

/**
 * An expedition in a project of the given group.
 */
function groupExpedition(Group $group, string $title, bool $completed = false): Expedition
{
    return Expedition::factory()->create([
        'project_id' => Project::factory()->create(['group_id' => $group->id])->id,
        'title' => $title,
        'completed' => $completed ? 1 : 0,
    ]);
}

it('lists the expeditions of the group projects only, linked and with their status', function () {
    $group = Group::factory()->create();
    $expedition = groupExpedition($group, 'Fall Flowers', completed: true);
    groupExpedition(Group::factory()->create(), 'Someone Elses Ferns');

    Livewire::test(GroupExpeditionsTable::class, ['group' => $group])
        ->assertSeeHtml('href="'.route('admin.expeditions.show', [$expedition]).'"')
        ->assertSeeInOrder(['Fall Flowers', 'Completed'])
        ->assertDontSee('Someone Elses Ferns');
});

it('searches expedition titles', function () {
    $group = Group::factory()->create();
    groupExpedition($group, 'Fall Flowers');
    groupExpedition($group, 'Spring Ferns');

    Livewire::test(GroupExpeditionsTable::class, ['group' => $group])
        ->set('search', 'fern')
        ->assertSee('Spring Ferns')
        ->assertDontSee('Fall Flowers');
});

it('sorts by title, then by status when its header is clicked', function () {
    $group = Group::factory()->create();
    groupExpedition($group, 'Bog Plants', completed: true);
    groupExpedition($group, 'Aster Relatives');
    groupExpedition($group, 'Cactus Survey');

    Livewire::test(GroupExpeditionsTable::class, ['group' => $group])
        ->assertSeeInOrder(['Aster Relatives', 'Bog Plants', 'Cactus Survey'])
        ->call('sortBy', 'completed')
        ->call('sortBy', 'completed')
        ->assertSeeInOrder(['Bog Plants', 'Aster Relatives', 'Cactus Survey']);
});

it('keeps its page in the URL under its own name', function () {
    $group = Group::factory()->create();
    foreach (range(1, 11) as $number) {
        groupExpedition($group, sprintf('Expedition %02d', $number));
    }

    Livewire::test(GroupExpeditionsTable::class, ['group' => $group])
        ->call('gotoPage', 2, 'expeditionsPage')
        ->assertSet('paginators.expeditionsPage', 2)
        ->assertSee('Expedition 11')
        ->assertDontSee('Expedition 10');
});
