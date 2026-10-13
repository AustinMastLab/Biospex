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
 * The subjects grid and what it needs, loaded by admin-app.js only on pages with #jqGridTable.
 *
 * jQuery UI is only used by the grid's column chooser (the multiselect and sortable widgets). It's the prebuilt
 * bundle: the individual widget files only load their dependencies (the widget factory, mouse, position) through
 * AMD, which Vite doesn't use.
 */
import "jquery-ui/dist/jquery-ui.js";
import "./ui.multiselect.js";
import "./jqgrid-setup.js";
import "./jqgrid.js";
