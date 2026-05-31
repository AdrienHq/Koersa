import { Controller } from '@hotwired/stimulus';

// Opens any form page (record / import / edit) inside a <dialog>, intercepts the
// submit, and on success refreshes the dashboard in place via Turbo — no reload.
export default class extends Controller {
    static targets = ['dialog', 'body'];

    async open(event) {
        event.preventDefault();
        const url = event.currentTarget.href ?? event.currentTarget.dataset.url;
        if (!url) return;

        await this.load(url);
        this.dialogTarget.showModal();
    }

    async submit(event) {
        const form = event.target.closest('form');
        if (!form) return;
        event.preventDefault();

        const method = (form.method || 'POST').toUpperCase();
        const response = await fetch(form.action, {
            method,
            body: method === 'GET' ? null : new FormData(form),
            headers: { 'Accept': 'text/html' },
            redirect: 'manual',
        });

        // Manual redirect mode: a 3xx comes back as an opaque redirect (status 0),
        // which means the server accepted the form and wants us to navigate away.
        if (response.type === 'opaqueredirect') {
            this.dialogTarget.close();
            if (window.Turbo) {
                window.Turbo.visit(window.location.href);
            } else {
                window.location.reload();
            }
            return;
        }

        // 200 means validation failed and the form was re-rendered; show the
        // errors inside the modal.
        const html = await response.text();
        this.inject(html);
    }

    close() {
        this.dialogTarget.close();
    }

    async load(url) {
        const response = await fetch(url, { headers: { 'Accept': 'text/html' } });
        const html = await response.text();
        this.inject(html);
    }

    inject(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const content = doc.querySelector('[data-modal-content]') ?? doc.querySelector('main');
        this.bodyTarget.replaceChildren();
        if (content) this.bodyTarget.append(...content.childNodes);
    }
}
