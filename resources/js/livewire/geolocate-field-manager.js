/*
 * Copyright (C) 2014 - 2025, Biospex
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
 * GeolocateFieldManager - Modern ES6 class for handling geolocate field functionality
 * Manages modal integration, form submission and tooltips for the Livewire field rows.
 * The field rows are native selects, so Livewire renders and updates them directly.
 */
export class GeolocateFieldManager {
    /**
     * Create a GeolocateFieldManager instance
     * @param {Object} options - Configuration options
     * @param {boolean} options.debug - Enable debug logging
     */
    constructor(options = {}) {
        this.options = {
            debug: false,
            ...options
        };

        this.isFormSubmitting = false;
        this.eventListeners = [];
        this.log('GeolocateFieldManager initialized with options:', this.options);
    }

    /**
     * Initialize the manager and set up all event listeners
     */
    init() {
        this.log('Initializing GeolocateFieldManager');

        this._setupModalEventListeners();
        this._setupFormEventListeners();
        this._setupTooltips();
        this._setupWireClickHandlers();

        this.log('GeolocateFieldManager fully initialized');
    }

    /**
     * Clean up and destroy all event listeners
     */
    destroy() {
        this.log('Destroying GeolocateFieldManager');

        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];

        this.log('GeolocateFieldManager destroyed');
    }

    /**
     * Set up modal event listeners
     * @private
     */
    _setupModalEventListeners() {
        const modalElement = document.getElementById('global-modal');
        if (!modalElement) {
            this.log('No global modal found, skipping modal event setup');
            return;
        }

        const showHandler = () => this._handleModalShow();
        const hideHandler = () => this._handleModalHide();

        modalElement.addEventListener('modal:shown', showHandler);
        modalElement.addEventListener('modal:hidden', hideHandler);
        this.eventListeners.push(
            { element: modalElement, event: 'modal:shown', handler: showHandler },
            { element: modalElement, event: 'modal:hidden', handler: hideHandler }
        );

        this.log('Modal event listeners set up');
    }

    /**
     * Handle modal show event
     * @private
     */
    _handleModalShow() {
        this.log('Modal shown - resetting GeoLocate fields');
        this.isFormSubmitting = false;

        setTimeout(() => this._dispatchReset(), 100);
    }

    /**
     * Handle modal hide event
     * @private
     */
    _handleModalHide() {
        this.log('Modal hidden - resetting GeoLocate fields');
        this.isFormSubmitting = true;

        this._dispatchReset();

        // Reset flag after cleanup
        setTimeout(() => {
            this.isFormSubmitting = false;
        }, 200);
    }

    /**
     * Reset the Livewire field rows.
     * @private
     */
    _dispatchReset() {
        if (!window.Livewire) {
            return;
        }

        try {
            window.Livewire.dispatch('resetGeolocateFields');
        } catch (error) {
            this.log('Could not dispatch resetGeolocateFields:', error);
        }
    }

    /**
     * Set up form event listeners
     * @private
     */
    _setupFormEventListeners() {
        const submitHandler = (event) => this._handleFormSubmit(event);

        $(document).on('submit', 'form#geolocate-form', submitHandler);

        this.log('Form event listeners set up');
    }

    /**
     * Handle form submission
     * @param {Event} event - Form submit event
     * @private
     */
    _handleFormSubmit(event) {
        this.log('Form submission detected');
        this.isFormSubmitting = true;

        // Stop Livewire updates during form submission
        if (window.Livewire) {
            try {
                window.Livewire.stop();
            } catch (error) {
                this.log('Could not stop Livewire:', error);
            }
        }
    }

    /**
     * Set up tooltips
     * @private
     */
    _setupTooltips() {
        try {
            $('[data-hover="tooltip"]').tooltip();
            this.log('Tooltips initialized');
        } catch (error) {
            this._handleError('Error setting up tooltips', error);
        }
    }

    /**
     * Set up wire:click event handlers
     * @private
     */
    _setupWireClickHandlers() {
        const clickHandler = () => this._handleWireClick();

        $(document).on('click', '[wire\\:click="addField"], [wire\\:click="removeField"]', clickHandler);

        this.log('Wire click handlers set up');
    }

    /**
     * Handle wire:click events: the add and remove buttons are re-rendered, so set up their tooltips again.
     * @private
     */
    _handleWireClick() {
        this.log('Wire click detected - refreshing tooltips');

        setTimeout(() => this._setupTooltips(), 100);
    }

    /**
     * Handle errors with proper logging
     * @param {string} message - Error message
     * @param {Error} error - Error object
     * @private
     */
    _handleError(message, error) {
        const errorMsg = `GeolocateFieldManager: ${message}`;
        if (this.options.debug) {
            console.error(errorMsg, error);
        } else {
            console.warn(errorMsg, error.message);
        }
    }

    /**
     * Debug logging
     * @param {...any} args - Arguments to log
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[GeolocateFieldManager]', ...args);
        }
    }
}
