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
 * Manages Bootstrap Select initialization, modal integration, and Livewire event handling
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
        
        // Initialize Bootstrap Select on initial load
        this._initializeBootstrapSelect();
        
        // Set up all event listeners
        this._setupLivewireEventListeners();
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
        
        // Clean up Bootstrap Select
        this._destroyBootstrapSelect();
        
        // Remove all event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];
        
        this.log('GeolocateFieldManager destroyed');
    }

    /**
     * Initialize Bootstrap Select components for geolocate fields
     * @private
     */
    _initializeBootstrapSelect() {
        try {
            const selects = document.querySelectorAll('.geolocate-field, .header-select');
            selects.forEach(select => {
                if (!select.classList.contains('bootstrap-select')) {
                    $(select).selectpicker();
                }
            });
            this.log('Bootstrap Select initialized for', selects.length, 'elements');
        } catch (error) {
            this._handleError('Error initializing Bootstrap Select', error);
        }
    }

    /**
     * Refresh Bootstrap Select initialization with flicker prevention
     * @private
     */
    _refreshBootstrapSelect() {
        if (this.isFormSubmitting) {
            this.log('Skipping Bootstrap Select refresh - form is submitting');
            return;
        }

        try {
            // Hide controls to prevent flicker
            const controls = document.querySelectorAll('#controls, .controls');
            controls.forEach(control => control.classList.add('bootstrap-select-updating'));

            // Use requestAnimationFrame for smooth DOM manipulation
            requestAnimationFrame(() => {
                this._destroyBootstrapSelect();

                setTimeout(() => {
                    // Re-initialize select elements
                    const selects = document.querySelectorAll('.geolocate-field, .header-select');
                    selects.forEach(select => {
                        $(select).selectpicker();
                    });

                    // Call makeSelect for compatibility
                    if (typeof window.makeSelect === 'function') {
                        document.querySelectorAll('.entry').forEach(entry => {
                            window.makeSelect($(entry));
                        });
                    }

                    // Remove hiding class
                    setTimeout(() => {
                        controls.forEach(control => control.classList.remove('bootstrap-select-updating'));
                    }, 10);

                    this.log('Bootstrap Select refreshed');
                }, 30);
            });
        } catch (error) {
            this._handleError('Error refreshing Bootstrap Select', error);
        }
    }

    /**
     * Destroy Bootstrap Select components
     * @private
     */
    _destroyBootstrapSelect() {
        try {
            const selects = document.querySelectorAll('.geolocate-field, .header-select');
            selects.forEach(select => {
                if (select.classList.contains('bootstrap-select')) {
                    $(select).selectpicker('destroy');
                }
            });
            this.log('Bootstrap Select destroyed for', selects.length, 'elements');
        } catch (error) {
            this._handleError('Error destroying Bootstrap Select', error);
        }
    }

    /**
     * Set up Livewire event listeners
     * @private
     */
    _setupLivewireEventListeners() {
        const livewireEvents = [
            { event: 'livewire:navigated', handler: () => this._handleLivewireUpdate('navigated') },
            { event: 'livewire:updated', handler: () => this._handleLivewireUpdate('updated') },
            { event: 'livewire:load', handler: () => this._handleLivewireLoad() }
        ];

        livewireEvents.forEach(({ event, handler }) => {
            document.addEventListener(event, handler);
            this.eventListeners.push({ element: document, event, handler });
        });

        this.log('Livewire event listeners set up');
    }

    /**
     * Handle Livewire update events
     * @param {string} eventType - Type of Livewire event
     * @private
     */
    _handleLivewireUpdate(eventType) {
        if (this.isFormSubmitting) {
            this.log(`Skipping Livewire ${eventType} handling - form is submitting`);
            return;
        }

        setTimeout(() => {
            if (!this.isFormSubmitting) {
                this._refreshBootstrapSelect();
            }
        }, 50);
    }

    /**
     * Handle Livewire load events
     * @private
     */
    _handleLivewireLoad() {
        if (this.isFormSubmitting) {
            this.log('Skipping Livewire load handling - form is submitting');
            return;
        }

        setTimeout(() => {
            if (!this.isFormSubmitting) {
                this._initializeBootstrapSelect();
            }
        }, 50);
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

        this.log('Modal event listeners set up');
    }

    /**
     * Handle modal show event
     * @private
     */
    _handleModalShow() {
        this.log('Modal shown - initializing Bootstrap Select');
        this.isFormSubmitting = false;

        setTimeout(() => {
            this._initializeBootstrapSelect();
            
            // Reset Livewire component when modal opens
            if (window.Livewire) {
                try {
                    window.Livewire.dispatch('resetGeolocateFields');
                } catch (error) {
                    this.log('Could not dispatch resetGeolocateFields:', error);
                }
            }
        }, 100);
    }

    /**
     * Handle modal hide event
     * @private
     */
    _handleModalHide() {
        this.log('Modal hidden - cleaning up Bootstrap Select');
        this.isFormSubmitting = true;
        
        this._destroyBootstrapSelect();
        
        // Reset component when modal closes
        if (window.Livewire) {
            try {
                window.Livewire.dispatch('resetGeolocateFields');
            } catch (error) {
                this.log('Could not dispatch resetGeolocateFields:', error);
            }
        }

        // Reset flag after cleanup
        setTimeout(() => {
            this.isFormSubmitting = false;
        }, 200);
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

        // Clean up Bootstrap Select to prevent DOM conflicts
        setTimeout(() => {
            this._destroyBootstrapSelect();
        }, 10);
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
     * Handle wire:click events
     * @private
     */
    _handleWireClick() {
        this.log('Wire click detected - refreshing Bootstrap Select');
        
        setTimeout(() => {
            this._refreshBootstrapSelect();
            this._setupTooltips();
        }, 100);
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