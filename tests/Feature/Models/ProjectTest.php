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

use App\Models\Project;

it('saves the long description without styling or pasted markup', function () {
    $project = Project::factory()->create([
        'description_long' => '<style>p{color:red}</style><p style="font-size:20px"><span style="color:red">Plants</span> of <b>Florida</b></p>',
    ]);

    expect($project->fresh()->getRawOriginal('description_long'))->toBe('<p>Plants of <b>Florida</b></p>');
});
