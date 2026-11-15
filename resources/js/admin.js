$(function () {

    // Copy buttons: copy their data-clipboard-text with the Clipboard API.
    $(document).on('click', '.clipboard', function () {
        navigator.clipboard.writeText($(this).data('clipboard-text'))
            .then(() => notify('check-circle', 'Copied to clipboard.', 'success'))
            .catch(() => notify('exclamation-circle', 'Could not copy to the clipboard.', 'warning'))
    })

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

    // Rich text editor for the long description and mail message. Only loaded on pages with one.
    let richTextAreas = document.querySelectorAll('textarea.textarea')
    if (richTextAreas.length) {
        import('./rich-text-editor.js').then(({initRichTextEditors}) => initRichTextEditors(richTextAreas))
    }

    // Restore: banner picker behavior
    $('.project-banner').on('click', function () {
        let img = $(this).data('name')
        let $bannerFile = $('#banner-file')
        $bannerFile.val(img)
        $bannerFile.attr('value', img)
        $('#banner-img').attr('src', Laravel.habitatBannersPath + img)
        closeModal('project-banner-modal')
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