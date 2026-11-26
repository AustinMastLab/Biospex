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

/**
 * The expert review page (admin/reconcile/index): zoom the subject image (Panzoom, loaded by the page), go to a page
 * from either page select, and copy a participant's answer into the expert opinion it belongs to.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('expertReview', () => ({
        loading: true,
        panzoom: null,

        init() {
            this.panzoom = Panzoom(this.$refs.panzoom);
            this.$refs.panzoom.parentElement.addEventListener('wheel', this.panzoom.zoomWithWheel);

            // The image may have loaded before Alpine started.
            if (this.$refs.image.complete) {
                this.loading = false;
            }
        },

        zoomIn() {
            this.panzoom.zoomIn();
        },

        zoomOut() {
            this.panzoom.zoomOut();
        },

        resetZoom() {
            this.panzoom.reset();
        },

        goToPage(url) {
            window.location.replace(url);
        },

        /**
         * A participant's answer (a radio) becomes the expert opinion for its column (the textarea with that id).
         */
        useAnswer(radio) {
            const opinion = document.getElementById(radio.dataset.column);
            if (opinion) {
                opinion.value = radio.value;
            }
        },
    }));
});
