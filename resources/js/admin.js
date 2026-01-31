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

$(function () {

    function fixSummernoteA11y($textarea) {
        if (!$textarea || !$textarea.length) return;

        let textareaId = $textarea.attr('id');
        if (!textareaId) return;

        let $label = $('label[for="' + textareaId + '"]').first();

        // Find the generated editor for THIS textarea
        // Summernote typically inserts .note-editor after the textarea
        let $editor = $textarea.nextAll('.note-editor').first();
        if (!$editor.length) {
            // Fallback: sometimes it’s wrapped differently
            $editor = $textarea.closest('.note-editor');
        }

        if (!$editor.length) return;

        // 1) Give the editable textbox an accessible name
        if ($label.length) {
            if (!$label.attr('id')) {
                $label.attr('id', textareaId + '-label');
            }

            let $editable = $editor.find('.note-editable[role="textbox"]').first();
            if ($editable.length) {
                $editable.attr('aria-labelledby', $label.attr('id'));
                $editable.removeAttr('aria-label'); // avoid conflicting/empty labels
                $editable.removeAttr('title'); // optional: keep naming consistent
            }
        } else {
            // If there is no label for some reason, at least provide a non-empty name
            let $editable = $editor.find('.note-editable[role="textbox"]').first();
            if ($editable.length && !$editable.attr('aria-label') && !$editable.attr('aria-labelledby')) {
                $editable.attr('aria-label', 'Rich text editor');
            }
        }

        // 2) Remove prohibited aria-label from resize bar (axe: aria-prohibited-attr)
        $editor.find('.note-resizebar[aria-label]').removeAttr('aria-label');
    }

    let textarea = $('.textarea')
    if (textarea.length) {
        textarea.summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']]
            ],
            callbacks: {
                onInit: function () {
                    // Run fix after init; use a timeout to ensure generated DOM is present
                    let $t = $(this);

                    // If `this` isn't the textarea, try to find it:
                    if (!$t.is('textarea')) {
                        // Summernote stores the original textarea in the closest container; safest is:
                        // fall back to patching all textareas
                        $('.textarea').each(function () {
                            fixSummernoteA11y($(this));
                        });
                        return;
                    }

                    fixSummernoteA11y($t);
                    setTimeout(function () {
                        fixSummernoteA11y($t);
                    }, 0);
                }
            }
        });

        // Extra safety: patch after Summernote has had time to build the UI
        textarea.each(function () {
            let $t = $(this);
            setTimeout(function () {
                fixSummernoteA11y($t);
            }, 0);
        });
    }

    setInterval(function () {
        let $footer = $('#footer')
        let docHeight = $(window).height()
        let footerHeight = $footer.height()
        let footerTop = $footer.position().top + footerHeight
        let marginTop = (docHeight - footerTop + 10)

        if (footerTop < docHeight)
            $footer.css('margin-top', marginTop + 'px') // padding of 30 on footer
        else
            $footer.css('margin-top', '0px')
    }, 250);
})
