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

namespace Tests\Unit\Presenters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, PresenterTestHelpers::class);

it('renders teamJoinUrlIcon with required attributes and unique data-clipboard-text token', function () {
    $team1 = \Database\Factories\EventTeamFactory::new()->create(['title' => 'Team One']);
    $team2 = \Database\Factories\EventTeamFactory::new()->create(['title' => 'Team Two']);

    $out1 = $team1->present()->teamJoinUrlIcon();
    $out2 = $team2->present()->teamJoinUrlIcon();

    // The teamJoinUrlIcon doesn't have an aria-label, but it has a title.
    // Our helper now checks visible text for tokens when requireAriaLabel=false.
    $this->assertLinkRenderedAndSafe($out1, [$team1->title], false);
    $this->assertLinkRenderedAndSafe($out2, [$team2->title], false);

    // Basic anchor and span structure
    expect($out1)->toContain('<a')->toContain('</a>')->toContain('</span>');
    expect($out2)->toContain('<a')->toContain('</a>')->toContain('</span>');

    // Must include non-empty title and data-clipboard-text attributes
    expect($out1)->toMatch('/\btitle="[^"]+"/');
    expect($out1)->toMatch('/\bdata-clipboard-text="[^"]+"/');
    expect($out2)->toMatch('/\btitle="[^"]+"/');
    expect($out2)->toMatch('/\bdata-clipboard-text="[^"]+"/');

    // Extract data-clipboard-text values and ensure they contain the expected unique token (uuid)
    preg_match('/data-clipboard-text="([^"]+)"/', $out1, $m1);
    preg_match('/data-clipboard-text="([^"]+)"/', $out2, $m2);

    expect($m1[1] ?? '')->not->toBeEmpty();
    expect($m2[1] ?? '')->not->toBeEmpty();
    expect($m1[1])->toContain($team1->uuid);
    expect($m2[1])->toContain($team2->uuid);
    expect($m1[1])->not->toBe($m2[1]);

    // Attribute explosion quick check (<=3 empty attributes on <a>)
    preg_match('/<a\b[^>]*>/i', $out1, $a1);
    preg_match_all('/\s[a-zA-Z0-9_-]+=""/m', $a1[0] ?? '', $ea1);
    expect(count($ea1[0] ?? []))->toBeLessThanOrEqual(3);
});

it('handles danger strings in EventTeamPresenter without breaking attributes or markup', function (string $danger) {
    $team = \Database\Factories\EventTeamFactory::new()->create(['title' => $danger]);

    $out = $team->present()->teamJoinUrlIcon();

    // Still renders anchor and span end tags
    expect($out)->toContain('<a')->toContain('</a>')->toContain('</span>');

    // Attributes present and non-empty; ensure no raw quotes in attribute values
    preg_match('/\btitle="([^"]+)"/', $out, $t);
    preg_match('/\bdata-clipboard-text="([^"]+)"/', $out, $dct);

    expect($t[1] ?? '')->not->toBeEmpty();
    expect($dct[1] ?? '')->not->toBeEmpty();
    expect($t[1])->not->toContain('"');
    expect($dct[1])->not->toContain('"');

    // Attribute explosion quick check
    preg_match('/<a\b[^>]*>/i', $out, $a);
    preg_match_all('/\s[a-zA-Z0-9_-]+=""/m', $a[0] ?? '', $ea);
    expect(count($ea[0] ?? []))->toBeLessThanOrEqual(3);
})->with([
    'Normal Title',
    "Bob's Challenge",
    'Bob said "Hello"',
    'Bob\'s "Special" Challenge',
    '<script>alert(1)</script>',
    'Fish & Wildlife',
    'Café – São Paulo',
]);
