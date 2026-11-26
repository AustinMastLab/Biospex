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

// Copy buttons (EventTeamPresenter): copy their data-clipboard-text with the Clipboard API.
document.addEventListener('click', (event) => {
    const button = event.target.closest('.clipboard');
    if (!button) {
        return;
    }

    navigator.clipboard.writeText(button.dataset.clipboardText)
        .then(() => notify('check-circle', 'Copied to clipboard.', 'success'))
        .catch(() => notify('exclamation-circle', 'Could not copy to the clipboard.', 'warning'));
});

// Banner picker (admin/partials/project-banner-modal): fill the project form's banner file and preview, then close.
document.addEventListener('click', (event) => {
    const banner = event.target.closest('.project-banner');
    if (!banner) {
        return;
    }

    const file = banner.dataset.name;
    const bannerFile = document.getElementById('banner-file');
    if (bannerFile) {
        bannerFile.value = file;
        bannerFile.setAttribute('value', file);
    }
    document.getElementById('banner-img')?.setAttribute('src', Laravel.habitatBannersPath + file);
    closeModal('project-banner-modal');
});

// File inputs styled as Bootstrap custom files: show the chosen file's name in the label before the input.
document.addEventListener('change', (event) => {
    const input = event.target.closest('.custom-file-input');
    const label = input?.previousElementSibling;
    if (label?.classList.contains('custom-file-label')) {
        label.classList.add('selected');
        label.textContent = input.value.split('\\').pop();
    }
});

// Rich text editor for the long description and mail message. Only loaded on pages with one.
document.addEventListener('DOMContentLoaded', () => {
    const richTextAreas = document.querySelectorAll('textarea.textarea');
    if (richTextAreas.length) {
        import('./rich-text-editor.js').then(({initRichTextEditors}) => initRichTextEditors(richTextAreas));
    }
});

// Footer spacing: push the footer to the bottom of the window on short pages.
setInterval(() => {
    const footer = document.getElementById('footer');
    if (!footer) {
        return;
    }

    // Measured without the margin this sets (as jQuery's position() did), so it doesn't alternate between ticks.
    const footerBottom = footer.offsetTop - (parseFloat(footer.style.marginTop) || 0) + footer.offsetHeight;
    footer.style.marginTop = footerBottom < window.innerHeight ? `${window.innerHeight - footerBottom + 10}px` : '0px';
}, 250);

// Home page "load more" projects still uses jQuery (#666).
$(function () {
    let homeProjectList = $('a.home-project-list')
    homeProjectList.click(function (e) {
        let count = $(this).data('count')
        $.get($(this).attr('href') + '/' + count, function (data) {
            $('.recent-projects-pane').html(data)
            homeProjectList.data('count', count + 5)
        })
        e.preventDefault()
    })
});
