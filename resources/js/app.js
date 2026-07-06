import Trix from 'trix';

document.addEventListener('trix-before-initialize', () => {
    // Only one heading level ships by default - map it to <h2> since <h1> is
    // reserved for the page title itself.
    Trix.config.blockAttributes.heading1.tagName = 'h2';
});

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
