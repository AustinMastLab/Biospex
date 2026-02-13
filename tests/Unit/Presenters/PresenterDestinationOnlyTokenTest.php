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

use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

uses(TestCase::class, PresenterTestHelpers::class);

it('fails when a required token appears only in the link destination', function () {
    $token = 'SECRET_TOKEN';

    // HTML where token is only in href, not in aria-label or visible text
    $html = sprintf(
        '<a href="/some/path?token=%s" aria-label="Safe Label">Click Me</a>',
        $token
    );

    expect(fn () => $this->assertLinkRenderedAndSafe($html, [$token], requireAriaLabel: false))
        ->toThrow(AssertionFailedError::class, sprintf(
            'Token "%s" found in destination (href/data-*) but not in aria-label or visible link text.',
            $token
        ));
});

it('fails when a required token appears only in data-href destination', function () {
    $token = 'DATA_TOKEN';

    // HTML where token is only in data-href
    $html = sprintf(
        '<a data-href="/some/path/%s" aria-label="Label">Text</a>',
        $token
    );

    expect(fn () => $this->assertLinkRenderedAndSafe($html, [$token], requireAriaLabel: false))
        ->toThrow(AssertionFailedError::class, sprintf(
            'Token "%s" found in destination (href/data-*) but not in aria-label or visible link text.',
            $token
        ));
});

it('passes when required token is in aria-label even if also in destination', function () {
    $token = 'GOOD_TOKEN';

    $html = sprintf(
        '<a href="/path/%s" aria-label="Label with %s">Link</a>',
        $token,
        $token
    );

    $this->assertLinkRenderedAndSafe($html, [$token]);
});

it('passes when required token is in visible text even if also in destination', function () {
    $token = 'TEXT_TOKEN';

    $html = sprintf(
        '<a href="/path/%s" aria-label="Label">%s</a>',
        $token,
        $token
    );

    $this->assertLinkRenderedAndSafe($html, [$token], requireAriaLabel: false);
});
