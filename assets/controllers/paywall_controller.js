import { Controller } from '@hotwired/stimulus';

// One paywall dialog per page (rendered in base.html.twig); many triggers.
// Any element with data-action="paywall#open" intercepts its click and
// opens the dialog instead of executing the original action. Used to gate
// CSV import, PDF download and the Tax tab content for free users.
export default class extends Controller {
    static targets = ['dialog'];

    open(event) {
        event.preventDefault();
        if (this.hasDialogTarget) {
            this.dialogTarget.showModal();
        }
    }

    close() {
        if (this.hasDialogTarget) {
            this.dialogTarget.close();
        }
    }
}
