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

use App\Services\Html\RichTextSanitizer;

it('keeps the formatting the editor offers', function () {
    $html = '<p><b>Bold</b> <strong>strong</strong> <i>italic</i> <em>em</em> <u>under</u> <s>struck</s> x<sup>2</sup> H<sub>2</sub>O<br /></p>'
        .'<ul><li>one</li></ul><ol><li>two</li></ol>'
        .'<p><a href="https://www.biospex.org" target="_blank" rel="noopener">site</a> <a href="mailto:a&#64;example.org">mail</a></p>';

    expect((new RichTextSanitizer)->sanitize($html))->toBe($html);
});

it('removes font sizes, colours and styling but keeps the text', function () {
    $html = '<p style="font-size:18px;line-height:2"><span style="color:#ff0000">Red</span> <font face="Arial" size="4">text</font></p>';

    expect((new RichTextSanitizer)->sanitize($html))->toBe('<p>Red text</p>');
});

it('removes pasted Word markup, style blocks and scripts with their content', function () {
    $html = '<style>table.MsoNormalTable{mso-style-name:"Table Normal"}</style>'
        .'<!--[if gte mso 9]><xml><w:WordDocument>junk</w:WordDocument></xml><![endif]-->'
        .'<p class="MsoNormal">Kept<o:p>&nbsp;</o:p></p><script>alert(1)</script>';

    expect((new RichTextSanitizer)->sanitize($html))->toBe("<p>Kept\u{00A0}</p>");
});

it('removes links that are not web or email addresses', function () {
    $html = '<a href="javascript:alert(1)" onclick="alert(2)">bad</a>';

    expect((new RichTextSanitizer)->sanitize($html))->toBe('<a>bad</a>');
});

it('does not cut off long descriptions', function () {
    $html = '<p>'.str_repeat('a', 70000).'</p>';

    expect((new RichTextSanitizer)->sanitize($html))->toBe($html);
});

it('returns null and empty descriptions unchanged', function (?string $html) {
    expect((new RichTextSanitizer)->sanitize($html))->toBe($html);
})->with([null, '', '   ']);
