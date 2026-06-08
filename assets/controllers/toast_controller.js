import { Controller } from '@hotwired/stimulus';

// Auto-dismissing toast. Fades out after `duration` ms (default 4000), or on
// click of the close button. Removes itself from the DOM at the end so the
// stack collapses naturally.
export default class extends Controller {
    static values = { duration: { type: Number, default: 4000 } };

    connect() {
        this.timeout = window.setTimeout(() => this.dismiss(), this.durationValue);
    }

    disconnect() {
        window.clearTimeout(this.timeout);
    }

    dismiss() {
        window.clearTimeout(this.timeout);
        this.element.classList.add('opacity-0', 'translate-y-2');
        window.setTimeout(() => this.element.remove(), 200);
    }
}
