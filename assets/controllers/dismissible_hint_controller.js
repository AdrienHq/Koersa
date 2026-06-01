import { Controller } from '@hotwired/stimulus';

// Cookie-tracked one-shot hint. Element renders hidden until JS confirms the
// cookie is absent; on dismiss the cookie is set and the element disappears.
// Server-render-safe (no flash of unwanted content) because the element is
// hidden by default and JS reveals it.
export default class extends Controller {
    static values = { cookie: String };

    connect() {
        if (this.cookieValue && !this.hasCookie(this.cookieValue)) {
            this.element.classList.remove('hidden');
        }
    }

    dismiss() {
        if (this.cookieValue) {
            this.setCookie(this.cookieValue, '1', 365);
        }
        this.element.classList.add('hidden');
    }

    hasCookie(name) {
        return document.cookie.split(';').some((c) => c.trim().startsWith(`${name}=`));
    }

    setCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Lax`;
    }
}
