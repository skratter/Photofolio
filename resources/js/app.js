import Trix from 'trix';
import { marked } from 'marked';

document.addEventListener('alpine:init', () => {
    Alpine.data('photoMasonry', (photos) => ({
        index: null,
        photos,
        open(i) {
            this.index = i;
        },
        close() {
            this.index = null;
        },
        next() {
            this.index = (this.index + 1) % this.photos.length;
        },
        prev() {
            this.index = (this.index - 1 + this.photos.length) % this.photos.length;
        },
    }));

    // Livewire uploads every file bound to the same wire:model property in
    // ONE combined request - which runs straight into PHP's max_file_uploads
    // (20 by default) once more than 20 files are selected at once, with no
    // indication of which files made it in and which didn't. This splits the
    // selection into small batches and uploads them one at a time through a
    // hidden input that only this component ever touches, tracking each
    // file's status as its batch comes back from the server.
    Alpine.data('photoUploader', () => ({
        BATCH_SIZE: 15,
        queue: [],
        batches: [],
        batchIndex: 0,
        isDraggingOver: false,
        isUploading: false,
        progress: 0,

        get total() {
            return this.queue.length;
        },
        get finished() {
            return this.queue.filter((file) => file.status === 'fertig' || file.status === 'fehler').length;
        },

        handleFiles(fileList) {
            const files = Array.from(fileList);

            if (files.length === 0) {
                return;
            }

            this.queue = files.map((file) => ({ name: file.name, status: 'wartend' }));
            this.batches = [];

            for (let i = 0; i < files.length; i += this.BATCH_SIZE) {
                this.batches.push(files.slice(i, i + this.BATCH_SIZE));
            }

            this.batchIndex = 0;
            this.uploadNextBatch();
        },

        uploadNextBatch() {
            if (this.batchIndex >= this.batches.length) {
                return;
            }

            const batch = this.batches[this.batchIndex];

            batch.forEach((file) => {
                const entry = this.queue.find((queued) => queued.name === file.name && queued.status === 'wartend');

                if (entry) {
                    entry.status = 'laedt';
                }
            });

            const dataTransfer = new DataTransfer();
            batch.forEach((file) => dataTransfer.items.add(file));

            this.$refs.uploader.files = dataTransfer.files;
            this.$refs.uploader.dispatchEvent(new Event('change'));
        },

        handleBatchUploaded(results) {
            results.forEach(({ name, status }) => {
                const entry = this.queue.find((queued) => queued.name === name && queued.status === 'laedt');

                if (entry) {
                    entry.status = status === 'done' ? 'fertig' : 'fehler';
                }
            });

            this.batchIndex++;
            this.uploadNextBatch();
        },
    }));
});

document.addEventListener('trix-before-initialize', () => {
    // Only one heading level ships by default - map it to <h2> since <h1> is
    // reserved for the page title itself.
    Trix.config.blockAttributes.heading1.tagName = 'h2';
});

// Trix has no Markdown support of its own. Pasted Markdown (e.g. an
// AI-drafted text with "## Headings" and "**bold**") would otherwise show up
// as literal '#'/'*' characters. Browsers put a "text/html" flavor on the
// clipboard for nearly any copy from a web page (even a plain-looking code
// block), and Trix's own trix-paste event already prefers that HTML flavor
// over plain text - so listening for "text/plain" there never actually
// fires for copies made from a browser. Intercepting the native "paste"
// event here (capture phase, before Trix's own listener runs) lets us read
// the clipboard ourselves and decide based on the content, not on which
// flavor happened to win.
const MARKDOWN_PATTERN = /^#{1,6}\s|\*\*[^*]+\*\*|^>\s|`[^`]+`|\[[^\]]+\]\([^)]+\)/m;

document.addEventListener('paste', (event) => {
    // With existing content already in the editor, the caret can sit inside
    // a nested block element, and the browser may target the paste event at
    // that descendant instead of the <trix-editor> host itself - so this
    // walks up to find it instead of requiring an exact match.
    const trixEditor = event.target.closest ? event.target.closest('trix-editor') : null;

    if (!trixEditor) {
        return;
    }

    const text = event.clipboardData?.getData('text/plain');

    if (!text || !MARKDOWN_PATTERN.test(text)) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    // breaks: true turns single line breaks into <br> - plain CommonMark
    // otherwise only treats a blank line as a paragraph break, silently
    // joining e.g. separate address lines into one.
    const html = marked.parse(text, { breaks: true });
    const editor = trixEditor.editor;

    // editor.insertHTML() merges the parsed HTML into the document at the
    // current selection - and when that selection starts inside an existing
    // formatted block (e.g. after selecting all existing text, which starts
    // inside whatever block is first), Trix wraps the *entire* newly
    // inserted multi-block HTML in that block's formatting instead of
    // respecting each new block's own tag. Confirmed via logging: the
    // converted HTML itself was always correct, only insertHTML() mangled
    // it. When the whole document is selected (or it's empty - the common
    // case here, replacing/setting a page's full content), loadHTML() loads
    // fresh content directly with no merge step, sidestepping the bug
    // entirely. Only fall back to insertHTML() for a genuine partial-content
    // paste in the middle of otherwise untouched text.
    const [rangeStart, rangeEnd] = editor.getSelectedRange();
    const documentLength = editor.getDocument().getLength();
    const wholeDocumentSelected = rangeStart === 0 && rangeEnd >= documentLength - 1;

    if (wholeDocumentSelected) {
        editor.loadHTML(html);
    } else {
        editor.insertHTML(html);
    }
}, true);

/**
 * Uploads a Trix file attachment to the page-attachments endpoint and
 * finalizes it with the resulting public URL. If the page doesn't exist yet
 * (still composing a brand new page), the server creates a draft row and
 * returns its id via onPageCreated so the caller can track it.
 */
window.uploadPageAttachment = function (event, pageId, onPageCreated) {
    const attachment = event.attachment;

    if (!attachment.file) {
        return;
    }

    const formData = new FormData();
    formData.append('file', attachment.file);

    if (pageId) {
        formData.append('page_id', pageId);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/pages/attachments', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', (progressEvent) => {
        if (progressEvent.lengthComputable) {
            attachment.setUploadProgress((progressEvent.loaded / progressEvent.total) * 100);
        }
    });

    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            const data = JSON.parse(xhr.responseText);
            attachment.setAttributes({ url: data.url, href: data.url });

            if (onPageCreated && data.page_id) {
                onPageCreated(data.page_id);
            }
        }
    });

    xhr.send(formData);
};
