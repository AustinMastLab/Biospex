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
 * Register free-jqGrid on the global jQuery.
 *
 * As a CommonJS module, jqGrid exports a setup function instead of registering itself, so importing it isn't
 * enough: the function has to be called with the window and the jQuery to extend.
 */
import setUpJqGrid from "free-jqgrid/js/jquery.jqgrid.min";

setUpJqGrid(window, window.jQuery);
