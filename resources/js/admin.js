$(function () {

    function fixSummernoteA11y($textarea) {
        if (!$textarea || !$textarea.length) return;

        let textareaId = $textarea.attr('id');
        if (!textareaId) return;

        let $label = $('label[for="' + textareaId + '"]').first();

        // Find the generated editor for THIS textarea
        let $editor = $textarea.nextAll('.note-editor').first();
        if (!$editor.length) {
            $editor = $textarea.closest('.note-editor');
        }
        if (!$editor.length) return;

        // A) Patch the Summernote HELP modal footer ("Summernote 0.9.1 · Project · Issues")
        // Silktide flags this as “semantic links” because it’s navigation-like but not a list.
        $('.note-modal').each(function () {
            let $modal = $(this);

            // Look for the specific footer paragraph Summernote generates
            let $p = $modal.find('.modal-footer p.text-center').first();
            if (!$p.length) return;

            // Avoid re-patching
            if ($p.data('a11yPatched')) return;

            // Only patch the one that looks like the Summernote help footer
            let text = $p.text().replace(/\s+/g, ' ').trim();
            if (!text.toLowerCase().includes('summernote')) return;

            // Build a semantic list using the existing links (and any plain text chunks)
            let $ul = $('<ul class="note-a11y-help-links" role="list"></ul>');

            $p.contents().each(function () {
                // Keep anchors as items; keep meaningful text chunks too
                if (this.nodeType === Node.ELEMENT_NODE && this.tagName.toLowerCase() === 'a') {
                    $ul.append($('<li></li>').append($(this)));
                    return;
                }

                if (this.nodeType === Node.TEXT_NODE) {
                    let chunk = (this.textContent || '').replace(/\s+/g, ' ').trim();

                    // Drop separators like "·"
                    chunk = chunk.replace(/[·•|]/g, '').trim();
                    if (!chunk) return;

                    $ul.append($('<li></li>').text(chunk));
                }
            });

            if ($ul.children().length) {
                $p.empty().append($ul);
                $p.data('a11yPatched', true);
            }
        });

        // B) Remove Summernote link popover's empty anchor (<a target="_blank"></a>)
        // Silktide flags it as an inaccessible/screen-reader link (no href, no text).
        $editor.find('.note-link-popover a[target="_blank"]').each(function () {
            let $a = $(this);
            let href = ($a.attr('href') || '').trim();
            let text = ($a.text() || '').trim();

            if (href === '' && text === '') {
                $a.remove();
            }
        });

        // 1) Name the editable region
        if ($label.length) {
            if (!$label.attr('id')) {
                $label.attr('id', textareaId + '-label');
            }
            let $editable = $editor.find('.note-editable[role="textbox"]').first();
            if ($editable.length) {
                $editable.attr('aria-labelledby', $label.attr('id'));
                $editable.removeAttr('aria-label');
                $editable.removeAttr('title');
            }

            // NEW: also label Summernote "code view" textarea (note-codable)
            let $codable = $editor.find('textarea.note-codable').first();
            if ($codable.length) {
                $codable.attr('aria-labelledby', $label.attr('id'));
                $codable.attr('aria-label', $label.text().trim() + ' (HTML source)');
            }
        } else {
            let $editable = $editor.find('.note-editable[role="textbox"]').first();
            if ($editable.length && !$editable.attr('aria-label') && !$editable.attr('aria-labelledby')) {
                $editable.attr('aria-label', 'Rich text editor');
            }

            // NEW: fallback label for code view
            let $codable = $editor.find('textarea.note-codable').first();
            if ($codable.length && !$codable.attr('aria-label') && !$codable.attr('aria-labelledby')) {
                $codable.attr('aria-label', 'Rich text editor (HTML source)');
            }
        }

        // 2) Remove prohibited aria-label from resize bar
        $editor.find('.note-resizebar[aria-label]').removeAttr('aria-label');

        // 3) Label generated color pickers (Silktide “Field labels”)
        $editor.find('input[id^="backColorPicker-"]').each(function () {
            let $i = $(this);
            if (!$i.attr('aria-label')) $i.attr('aria-label', 'Background color');
        });
        $editor.find('input[id^="foreColorPicker-"]').each(function () {
            let $i = $(this);
            if (!$i.attr('aria-label')) $i.attr('aria-label', 'Text color');
        });

        // 4) “Semantic links” in dropdowns: wrap menu items in <ul><li>
        // Summernote uses these dropdown menus; sometimes they are rebuilt after init.
        $editor.find('.note-dropdown-menu, .dropdown-menu').each(function () {
            let $menu = $(this);

            if ($menu.children('ul.note-a11y-list').length) return;

            // Only wrap direct interactive children
            let $items = $menu.children('a, button');
            if (!$items.length) return;

            let $ul = $('<ul class="note-a11y-list" role="list"></ul>');
            $items.each(function () {
                $ul.append($('<li></li>').append($(this)));
            });

            $menu.empty().append($ul);
        });
    }

    function patchAllSummernotes() {
        $('.textarea').each(function () {
            fixSummernoteA11y($(this));
        });
    }

    // Datetimepicker a11y patch:
    // - add scope="col" to weekday header <th>
    // - label icon-only navigation buttons (prev/next) so Silktide doesn't report "Field labels"
    // - re-apply whenever the widget regenerates its calendar
    function patchDateTimePickerA11y(root) {
        const $root = root ? $(root) : $(document);

        $root.find('.xdsoft_calendar thead th').each(function () {
            const $th = $(this);

            // Only set what's missing so we don't fight the library
            if (!$th.attr('scope')) {
                $th.attr('scope', 'col');
            }
        });

        // Month navigation (datepicker)
        $root.find('.xdsoft_datetimepicker .xdsoft_datepicker .xdsoft_prev').each(function () {
            const $btn = $(this);
            if (!$btn.attr('aria-label')) $btn.attr('aria-label', 'Previous month');
            if (!$btn.attr('title')) $btn.attr('title', 'Previous month');
        });

        $root.find('.xdsoft_datetimepicker .xdsoft_datepicker .xdsoft_next').each(function () {
            const $btn = $(this);
            if (!$btn.attr('aria-label')) $btn.attr('aria-label', 'Next month');
            if (!$btn.attr('title')) $btn.attr('title', 'Next month');
        });

        // TODAY button (icon-only) — this is commonly the last remaining “field labels” warning
        $root.find('.xdsoft_datetimepicker .xdsoft_today_button').each(function () {
            const $btn = $(this);
            if (!$btn.attr('aria-label')) $btn.attr('aria-label', 'Today');
            if (!$btn.attr('title')) $btn.attr('title', 'Today');
        });

        // Time scrollers (timepicker) — label accurately (avoid “month” wording here)
        $root.find('.xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_prev').each(function () {
            const $btn = $(this);
            if (!$btn.attr('aria-label')) $btn.attr('aria-label', 'Scroll time up');
            if (!$btn.attr('title')) $btn.attr('title', 'Scroll time up');
        });

        $root.find('.xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_next').each(function () {
            const $btn = $(this);
            if (!$btn.attr('aria-label')) $btn.attr('aria-label', 'Scroll time down');
            if (!$btn.attr('title')) $btn.attr('title', 'Scroll time down');
        });
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
                    patchAllSummernotes();
                    setTimeout(patchAllSummernotes, 0);
                }
            }
        });
    }

    // Keep patching as Summernote/Bootstrap rebuilds menus/toolbars dynamically.
    // This is what usually makes “nothing changed” happen with a11y scanners.
    let observer = new MutationObserver(function (mutations) {
        for (let i = 0; i < mutations.length; i++) {
            let m = mutations[i];
            if (!m.addedNodes || !m.addedNodes.length) continue;

            for (let j = 0; j < m.addedNodes.length; j++) {
                let node = m.addedNodes[j];
                if (!node || node.nodeType !== 1) continue;

                // If Summernote UI or dropdown menus were added/changed, re-patch.
                if (
                    node.matches('.note-editor, .note-dropdown-menu, .dropdown-menu') ||
                    (node.querySelector && node.querySelector('.note-editor, .note-dropdown-menu, .dropdown-menu'))
                ) {
                    patchAllSummernotes();
                    // quick exit for this batch
                    i = mutations.length;
                    break;
                }

                // If datetimepicker/calendar was added or rebuilt, patch header scopes
                if (
                    node.matches('.xdsoft_datetimepicker, .xdsoft_calendar') ||
                    (node.querySelector && node.querySelector('.xdsoft_datetimepicker, .xdsoft_calendar'))
                ) {
                    patchDateTimePickerA11y(node);
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

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
        ],
        onShow: function () {
            // Patch immediately and on next tick (some scanners snapshot fast)
            patchDateTimePickerA11y(document);
            setTimeout(function () {
                patchDateTimePickerA11y(document);
            }, 0);
        },
        onGenerate: function () {
            // Fired when the widget regenerates its HTML (month nav, etc.)
            patchDateTimePickerA11y(document);
        }
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

/**
 * Improves DataTables pagination accessibility:
 * - Adds aria-label/title to "..." (ellipsis) buttons
 * - Adds aria-label/title to page number buttons
 * - Adds aria-current="page" to current page
 * - Adds aria-disabled for disabled controls
 *
 * @param {string} tableId - The table element id WITHOUT '#'
 */
window.improveDataTablePaginationA11y = function improveDataTablePaginationA11y(tableId) {
    const $paginate = $('#' + tableId + '_paginate');
    if ($paginate.length === 0) return;

    // Patch the special ellipsis control by id (DataTables uses `${tableId}_ellipsis`)
    const $ellipsisLink = $('#' + tableId + '_ellipsis').find('a.page-link, a.paginate_button, a');
    if ($ellipsisLink.length) {
        // Ensure link purpose is clear from text INSIDE the link
        // (some scanners ignore aria-label for this check)
        const hasSrText = $ellipsisLink.find('span.sr-only[data-dt-ellipsis-a11y="1"]').length > 0;
        if (!hasSrText) {
            $ellipsisLink.html('&hellip;<span class="sr-only" data-dt-ellipsis-a11y="1"> More pages</span>');
        }

        $ellipsisLink.attr({
            'aria-label': 'More pages',
            'title': 'More pages'
        });
    }

    // Patch all pagination links
    $paginate.find('a.paginate_button, a.page-link').each(function () {
        const $btn = $(this);
        const rawText = ($btn.text() || '').trim();

        $btn.attr('aria-disabled', $btn.closest('.disabled').length ? 'true' : ($btn.hasClass('disabled') ? 'true' : 'false'));

        if ($btn.closest('.active,.current').length || $btn.hasClass('current')) {
            $btn.attr('aria-current', 'page');
        } else {
            $btn.removeAttr('aria-current');
        }

        // Don’t overwrite the ellipsis we already enhanced above
        if (rawText === '…' || rawText === '...') {
            $btn.attr({ 'aria-label': 'More pages', 'title': 'More pages' });
            return;
        }

        if (/^\d+$/.test(rawText)) {
            $btn.attr({ 'aria-label': 'Go to page ' + rawText, 'title': 'Go to page ' + rawText });
        } else if (rawText.toLowerCase() === 'previous') {
            $btn.attr({ 'aria-label': 'Go to previous page', 'title': 'Go to previous page' });
        } else if (rawText.toLowerCase() === 'next') {
            $btn.attr({ 'aria-label': 'Go to next page', 'title': 'Go to next page' });
        }
    });
};