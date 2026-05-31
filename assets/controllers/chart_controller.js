import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

// One controller per chart card. Reads a JSON config from the value attribute
// and renders it into the canvas inside. Generic on purpose — every overview
// chart (line, doughnut, bar) goes through this single hook.
Chart.register(...registerables);

export default class extends Controller {
    static values = { config: Object };
    static targets = ['canvas'];

    connect() {
        if (!this.hasCanvasTarget || !this.configValue) return;
        this.chart = new Chart(this.canvasTarget, this.configValue);
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}
