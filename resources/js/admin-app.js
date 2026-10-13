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

// Imports run in the order written: jQuery and the libraries first, then the page scripts that use them.
import "./jquery-global.js";
import "./app.js";
import "./admin-globals.js";
import "bootstrap-select/dist/js/bootstrap-select.min";
import "bootstrap-notify/bootstrap-notify";
import "./common.js";
import "./admin.js";
import "./admin-modals.js";
import "./confirm-dialog.js";

// The subjects grid (jqGrid and jQuery UI) is only loaded on the pages that have one. Summernote and the date
// picker are loaded by admin.js in the same way.
if (document.getElementById('jqGridTable')) {
    import("./admin-grid.js");
}
