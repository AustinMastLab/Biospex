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
 * Livewire Components Module Index
 * Entry point for all Livewire-related JavaScript functionality
 */

// Import all managers
import {LivewireFileUploadHandler} from './file-upload-handler.js';
import {ProjectManager} from './project-manager.js';
import {ExpeditionManager} from './expedition-manager.js';
import {EventTeamManager} from './event-team-manager.js';

// Export classes for use in blade templates
export {
    LivewireFileUploadHandler,
    ProjectManager,
    ExpeditionManager,
    EventTeamManager
};

// Make classes available globally for backward compatibility
window.LivewireFileUploadHandler = LivewireFileUploadHandler;
window.ProjectManager = ProjectManager;
window.ExpeditionManager = ExpeditionManager;
window.EventTeamManager = EventTeamManager;

// Auto-initialize based on current page context
document.addEventListener('DOMContentLoaded', function () {
    // Determine which manager to initialize based on page context
    const body = document.body;
    const isProjectPage = body.classList.contains('project-page') ||
        document.querySelector('form[action*="projects"]') ||
        document.getElementById('logo_path'); // Project logo field

    const isExpeditionPage = body.classList.contains('expedition-page') ||
        document.querySelector('form[action*="expeditions"]') ||
        (document.getElementById('logo_path') && window.location.href.includes('expedition'));

    if (isProjectPage && !isExpeditionPage) {
        const projectManager = new ProjectManager({debug: false});
        projectManager.init();
    } else if (isExpeditionPage) {
        const expeditionManager = new ExpeditionManager({debug: false});
        expeditionManager.init();
    }
});