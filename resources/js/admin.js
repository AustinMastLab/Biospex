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

    // Restore: clipboard support used in admin pages
    let clipboard = new ClipboardJS('.clipboard');

    // Restore: user group toggle
    let userGroup = $('#user-group')
    let groupInput = $('#group-input')
    userGroup.change(function () {
        this.value === 'new' ? groupInput.show() : groupInput.hide()
    })
    if (userGroup.length) {
        userGroup.val() === 'new' ? groupInput.show() : groupInput.hide()
    }

    // Restore: home project list "load more"
    let homeProjectList = $('a.home-project-list')
    homeProjectList.click(function (e) {
        let count = $(this).data('count')
        $.get($(this).attr('href') + '/' + count, function (data) {
            $('.recent-projects-pane').html(data)
            homeProjectList.data('count', count + 5)
        })
        e.preventDefault()
    })

    // Summernote init + updated a11y patching
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
                    let $t = $(this);

                    // If `this` isn't the textarea, patch all (safe fallback)
                    if (!$t.is('textarea')) {
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

    // Restore: datetimepicker init
    $.datetimepicker.setLocale('en')
    $('.date-time-picker').datetimepicker({
        format: 'Y-m-d H:i',
        allowTimes: [
            '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30',
            '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30',
            '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
            '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30',
            '20:00', '20:30', '21:00', '21:30', '22:00', '22:30'
        ]
    })

    // Restore: confirm + method spoofing + csrf via bootbox
    $(document).on('click', '[data-confirm=confirmation]', function (e) {
        e.preventDefault();

        let $trigger = $(this);
        let url = $trigger.is("[data-href]") ? $trigger.data("href") : $trigger.attr('href')
        let method = $trigger.data('method')

        bootbox.confirm({
            title: $trigger.data('title'),
            message: $trigger.data('content'),
            buttons: {
                cancel: {
                    label: '<i class="fas fa-times-circle"></i> Cancel',
                    className: 'btn btn-primary'
                },
                confirm: {
                    label: '<i class="fas fa-check-circle"></i> Confirm',
                    className: 'btn btn-primary'
                }
            },
            callback: function (result) {
                if (!result) return;

                $trigger.append(function () {
                    let methodForm = "\n"
                    methodForm += "<form action='" + url + "' method='POST' style='display:none'>\n"
                    methodForm += "<input type='hidden' name='_method' value='" + method + "'>\n"
                    methodForm += "<input type='hidden' name='_token' value='" + $('meta[name=csrf-token]').attr('content') + "'>\n"
                    methodForm += "</form>\n"
                    return methodForm
                }).find('form').last().submit()
            }
        })
    })

    // Restore: banner picker behavior
    $('.project-banner').on('click', function () {
        let img = $(this).data('name')
        let $bannerFile = $('#banner-file')
        $bannerFile.val(img)
        $bannerFile.attr('value', img)
        $('#banner-img').attr('src', Laravel.habitatBannersPath + img)
        $("#project-banner-modal .close").click()
    })

    // Restore: custom-file-input label update
    $(document).on('change', '.custom-file-input', function () {
        let fileName = $(this).val().split('\\').pop()
        $(this).prev('.custom-file-label').addClass("selected").html(fileName)
    })

    // Footer spacing (kept)
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