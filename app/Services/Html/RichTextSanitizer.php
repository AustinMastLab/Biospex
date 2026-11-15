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

namespace App\Services\Html;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans rich text (project descriptions) down to what the rich text editor offers:
 * paragraphs, line breaks, bold, italic, underline, strikethrough, superscript, subscript, lists and links.
 *
 * Any other element is unwrapped and its text kept, so font sizes, colours, line heights, <font> and <span>
 * styling and pasted Word markup are removed. <style>, <script>, <title> and Word's <xml> blocks are removed
 * with their content. Links keep only http, https and mailto addresses.
 */
class RichTextSanitizer
{
    /**
     * Elements that are removed together with their content.
     *
     * The sanitizer only drops head elements (style, script, title) in a document head, so they are removed first.
     */
    private const REMOVED_WITH_CONTENT = ['style', 'script', 'title', 'xml'];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowElement('p')
            ->allowElement('div')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('sup')
            ->allowElement('sub')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href', 'target', 'rel'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->withMaxInputLength(-1);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Clean the HTML. Null and empty input come back unchanged.
     */
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $elements = implode('|', self::REMOVED_WITH_CONTENT);
        $html = preg_replace("#<({$elements})\\b[^>]*>.*?</\\1\\s*>#is", '', $html) ?? $html;

        return $this->sanitizer->sanitize($html);
    }
}
