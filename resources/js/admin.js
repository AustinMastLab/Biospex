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
