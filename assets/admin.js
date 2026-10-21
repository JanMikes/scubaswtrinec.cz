// Admin: navigation toggle, confirm dialogs, CKEditor 5 (GPL) on [data-editor="rich"].
import { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Link, AutoLink, List, BlockQuote,
    Table, TableToolbar, HorizontalLine, SpecialCharacters, SpecialCharactersEssentials, RemoveFormat, PasteFromOffice,
    MediaEmbed, Image, ImageInsertViaUrl, ImageToolbar, ImageStyle, ImageResize, ImageUpload, SimpleUploadAdapter,
    SourceEditing, GeneralHtmlSupport, HtmlEmbed, Alignment, Autoformat } from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import cs from 'ckeditor5/translations/cs.js';

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-admin-nav-toggle]');
    const nav = document.querySelector('[data-admin-nav]');
    toggle?.addEventListener('click', () => nav?.classList.toggle('hidden'));

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => { if (!confirm(form.dataset.confirm)) e.preventDefault(); });
    });

    const uploadUrl = document.body.dataset.editorUpload;
    const csrf = document.body.dataset.editorCsrf;
    document.querySelectorAll('textarea[data-editor="rich"]').forEach((el) => {
        ClassicEditor.create(el, {
            licenseKey: 'GPL',
            language: 'cs',
            translations: [cs],
            plugins: [Essentials, Paragraph, Heading, Bold, Italic, Underline, Link, AutoLink, List, BlockQuote, Table, TableToolbar,
                HorizontalLine, SpecialCharacters, SpecialCharactersEssentials, RemoveFormat, PasteFromOffice, MediaEmbed, Image,
                ImageInsertViaUrl, ImageToolbar, ImageStyle, ImageResize, ImageUpload, SimpleUploadAdapter, SourceEditing,
                GeneralHtmlSupport, HtmlEmbed, Alignment, Autoformat],
            toolbar: { items: ['heading', '|', 'bold', 'italic', 'underline', 'removeFormat', '|', 'link', 'bulletedList', 'numberedList',
                'blockQuote', 'alignment', '|', 'insertImage', 'mediaEmbed', 'insertTable', 'horizontalLine', 'specialCharacters', 'htmlEmbed',
                '|', 'undo', 'redo', '|', 'sourceEditing'], shouldNotGroupWhenFull: false },
            heading: { options: [
                { model: 'paragraph', title: 'Odstavec', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Nadpis', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Podnadpis', class: 'ck-heading_heading3' },
            ] },
            image: { toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'imageTextAlternative'] },
            table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
            mediaEmbed: { previewsInData: true },
            // Keep every piece of existing HTML (YouTube iframes, inline styles, classes) intact.
            htmlSupport: { allow: [{ name: /.*/, attributes: true, classes: true, styles: true }] },
            simpleUpload: uploadUrl ? { uploadUrl, headers: { 'X-CSRF-Token': csrf } } : undefined,
        }).then((editor) => {
            editor.editing.view.change((writer) => writer.setStyle('min-height', '320px', editor.editing.view.document.getRoot()));
            el.form?.addEventListener('submit', () => editor.updateSourceElement());
        }).catch((err) => console.error(err));
    });
});
