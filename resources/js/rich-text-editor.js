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

import {Editor} from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';

/**
 * Toolbar buttons: label, Font Awesome icon, the command to run and the mark or node that shows it as active.
 * The editor offers only these, the same set RichTextSanitizer keeps on the server.
 */
const BUTTONS = [
    {label: 'Bold', icon: 'bold', run: chain => chain.toggleBold(), active: 'bold'},
    {label: 'Italic', icon: 'italic', run: chain => chain.toggleItalic(), active: 'italic'},
    {label: 'Underline', icon: 'underline', run: chain => chain.toggleUnderline(), active: 'underline'},
    {label: 'Strikethrough', icon: 'strikethrough', run: chain => chain.toggleStrike(), active: 'strike'},
    {label: 'Superscript', icon: 'superscript', run: chain => chain.toggleSuperscript(), active: 'superscript'},
    {label: 'Subscript', icon: 'subscript', run: chain => chain.toggleSubscript(), active: 'subscript'},
    {label: 'Bulleted list', icon: 'list-ul', run: chain => chain.toggleBulletList(), active: 'bulletList'},
    {label: 'Numbered list', icon: 'list-ol', run: chain => chain.toggleOrderedList(), active: 'orderedList'},
    {label: 'Link', icon: 'link', run: (chain, editor) => setLink(chain, editor), active: 'link'},
    {label: 'Remove link', icon: 'unlink', run: chain => chain.extendMarkRange('link').unsetLink()},
    {label: 'Clear formatting', icon: 'remove-format', run: chain => chain.unsetAllMarks().clearNodes()},
];

/**
 * Ask for a link address and apply it to the selection; an empty answer removes the link.
 */
function setLink(chain, editor) {
    const url = window.prompt('Link address', editor.getAttributes('link').href || 'https://');

    if (url === null) {
        return chain;
    }

    if (url.trim() === '' || url.trim() === 'https://') {
        return chain.extendMarkRange('link').unsetLink();
    }

    return chain.extendMarkRange('link').setLink({href: url.trim()});
}

/**
 * Replace each textarea with a rich text editor. The textarea stays in the form, hidden, and always holds the
 * editor's HTML, so the form posts the same field as before.
 *
 * @param {NodeListOf<HTMLTextAreaElement>|HTMLTextAreaElement[]} textareas
 */
export function initRichTextEditors(textareas) {
    textareas.forEach(textarea => initRichTextEditor(textarea));
}

/**
 * @param {HTMLTextAreaElement} textarea
 */
function initRichTextEditor(textarea) {
    const label = document.querySelector(`label[for="${textarea.id}"]`);
    const wrapper = document.createElement('div');
    wrapper.className = 'rte' + (textarea.classList.contains('is-invalid') ? ' is-invalid' : '');

    const toolbar = document.createElement('div');
    toolbar.className = 'rte-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', (label ? label.textContent.replace(/[:*]/g, '').trim() + ' ' : '') + 'formatting');

    const content = document.createElement('div');
    content.className = 'rte-content';

    wrapper.append(toolbar, content);
    textarea.insertAdjacentElement('afterend', wrapper);
    textarea.hidden = true;
    // A hidden required field can't be focused, so the browser would block the submit; the server still requires it.
    textarea.required = false;

    const editor = new Editor({
        element: content,
        extensions: [
            StarterKit.configure({
                heading: false,
                blockquote: false,
                code: false,
                codeBlock: false,
                horizontalRule: false,
                link: {openOnClick: false, defaultProtocol: 'https'},
            }),
            Subscript,
            Superscript,
        ],
        content: textarea.value,
        editorProps: {
            attributes: {
                class: 'rte-editable',
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-label': label ? label.textContent.replace(/[:*]/g, '').trim() : 'Text',
            },
        },
        onUpdate: ({editor}) => {
            textarea.value = editor.isEmpty ? '' : editor.getHTML();
            updateActiveButtons(editor, toolbar);
        },
        onSelectionUpdate: ({editor}) => updateActiveButtons(editor, toolbar),
    });

    BUTTONS.forEach(button => {
        const element = document.createElement('button');
        element.type = 'button';
        element.className = 'rte-button';
        element.title = button.label;
        element.setAttribute('aria-label', button.label);
        if (button.active) {
            element.dataset.active = button.active;
            element.setAttribute('aria-pressed', 'false');
        }
        element.innerHTML = `<i class="fas fa-${button.icon}" aria-hidden="true"></i>`;
        element.addEventListener('click', () => button.run(editor.chain().focus(), editor).run());
        toolbar.appendChild(element);
    });

    if (label) {
        label.addEventListener('click', event => {
            event.preventDefault();
            editor.commands.focus();
        });
    }

    // The stored HTML is replaced by the editor's version only when it is edited, so opening a form changes nothing.
    updateActiveButtons(editor, toolbar);
}

/**
 * Mark the toolbar buttons whose formatting applies at the cursor.
 */
function updateActiveButtons(editor, toolbar) {
    toolbar.querySelectorAll('[data-active]').forEach(element => {
        const isActive = editor.isActive(element.dataset.active);
        element.classList.toggle('is-active', isActive);
        element.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}
