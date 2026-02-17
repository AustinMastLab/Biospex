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

$.jgrid = $.jgrid || {};
$.jgrid.no_legacy_api = true;
$.jgrid.useJSON = true;
$.jgrid.defaults.responsive = true;
$.jgrid.cellattr = $.jgrid.cellattr || {};

$(function () {
    if ($('#jqGridTable').length > 0) {

        "use strict";
        let dataUrl = Laravel.dataUrl;
        let maxCount = Laravel.maxCount;
        let checkbox = Laravel.checkbox;
        let exportUrl = Laravel.exportUrl;
        let route = Laravel.route;
        let selected = Laravel.subjectIds;

        let model = JSON.parse(Laravel.model), cm = model.colModel, cn = model.colNames;

        let $grid = $("#jqGridTable");

        // Center and enlarge the loading message
        $('head').append(`
                <style>
                    #load_jqGridTable {
                        position: fixed !important;
                        top: 50% !important;
                        left: 50% !important;
                        transform: translate(-50%, -50%) !important;
                        z-index: 99999 !important;
                        font-size: 24px !important;
                        font-weight: bold;
                        padding: 20px 40px !important;
                        background: #ffffff !important;
                        border: 2px solid #cccccc !important;
                        border-radius: 5px;
                        box-shadow: 0 0 15px rgba(0,0,0,0.2);
                        opacity: 1 !important;
                        display: none;
                    }
                </style>
            `);

        mapFormatter(cm);

        let saveObjectInLocalStorage = function (storageItemName, object) {
                if (typeof window.localStorage !== 'undefined') {
                    window.localStorage.setItem(storageItemName, JSON.stringify(object));
                }
            },
            removeObjectFromLocalStorage = function (storageItemName) {
                if (typeof window.localStorage !== 'undefined') {
                    window.localStorage.removeItem(storageItemName);
                }
            },
            getObjectFromLocalStorage = function (storageItemName) {
                if (typeof window.localStorage !== 'undefined') {
                    return JSON.parse(window.localStorage.getItem(storageItemName));
                }
            },
            myColumnStateName = 'ColumnChooserAndLocalStorage.colState',
            saveColumnState = function (perm) {
                let colModel = this.jqGrid('getGridParam', 'colModel'), i, l = colModel.length, colItem, cmName,
                    postData = this.jqGrid('getGridParam', 'postData'),
                    columnsState = {
                        search: this.jqGrid('getGridParam', 'search'),
                        page: this.jqGrid('getGridParam', 'page'),
                        sortname: this.jqGrid('getGridParam', 'sortname'),
                        sortorder: this.jqGrid('getGridParam', 'sortorder'),
                        permutation: perm,
                        colStates: {}
                    },
                    colStates = columnsState.colStates;

                if (typeof (postData.filters) !== 'undefined') {
                    columnsState.filters = postData.filters;
                }

                for (i = 0; i < l; i++) {
                    colItem = colModel[i];
                    cmName = colItem.name;
                    if (cmName !== 'rn' && cmName !== 'cb' && cmName !== 'subgrid') {
                        colStates[cmName] = {
                            width: colItem.width,
                            hidden: colItem.hidden
                        };
                    }
                }
                saveObjectInLocalStorage(myColumnStateName, columnsState);
            },
            myColumnsState,
            isColState,
            restoreColumnState = function (colModel) {
                let colItem, i, l = colModel.length, colStates, cmName,
                    columnsState = getObjectFromLocalStorage(myColumnStateName);

                if (columnsState) {
                    if (columnsState.permutation && columnsState.permutation.length !== (l + (checkbox ? 1 : 0))) {
                        return null;
                    }
                    colStates = columnsState.colStates;
                    for (i = 0; i < l; i++) {
                        colItem = colModel[i];
                        cmName = colItem.name;
                        if (cmName !== 'rn' && cmName !== 'cb' && cmName !== 'subgrid') {
                            colModel[i] = $.extend(true, {}, colModel[i], colStates[cmName]);
                        }
                    }
                }
                return columnsState;
            },
            columnChooser = function () {
                $grid.jqGrid('columnChooser', {
                    classname: "columnChooser",
                    modal: true,
                    width: 600,
                    done: function (perm) {
                        if (perm) {
                            this.jqGrid("remapColumns", perm, true);
                        }
                    }
                });
                $('.ui-multiselect ul.selected').height('500px');
                $('.ui-multiselect ul.available').height('500px');
            },
            eraseSettings = function () {
                removeObjectFromLocalStorage(myColumnStateName);
                window.location.reload();
            },
            exportSettings = function () {
                let filters = $grid.jqGrid('getGridParam', "postData").filters;
                $.ajax({
                    type: 'post',
                    url: exportUrl,
                    data: {'filters': filters, 'route': route}
                }).done(function () {
                    bootboxMsg("Export Submitted", "You will receive an email when the export is complete.");
                }).fail(function () {
                    bootboxMsg("Export Error", "Export submission failed. Please contact Admin.");
                });
            },
            setSelectedCount = function () {
                $('#subject-count-html').html(selected.length);
                if (selected.length > maxCount) {
                    $('#max').addClass('red');
                }
            },
            updateIdsOfSelectedRows = function (id, isSelected) {
                let index = -1;
                let strId = String(id).trim();

                // Find index using robust string comparison
                for (let i = 0; i < selected.length; i++) {
                    if (String(selected[i]).trim() === strId) {
                        index = i;
                        break;
                    }
                }

                if (!isSelected && index >= 0) {
                    // Remove from array
                    selected.splice(index, 1);
                } else if (isSelected && index < 0) {
                    // Add to array
                    selected.push(strId);
                }
            },
            setSelected = function () {
                if (!checkbox) return;

                // Necessary to reset the selection so it works on first loading of page.
                $grid.jqGrid("resetSelection");

                let ids = $grid.jqGrid('getDataIDs');
                $.each(ids, function (index, rowId) {
                    let strRowId = String(rowId).trim();
                    let found = false;

                    // Check if ID is in the selected array using robust string comparison
                    for (let i = 0; i < selected.length; i++) {
                        if (String(selected[i]).trim() === strRowId) {
                            found = true;
                            break;
                        }
                    }

                    if (found) {
                        $grid.jqGrid('setSelection', rowId, false);
                    }
                });
            },
            applyJqGridA11yFixes = function () {
                // Fix axe: aria-required-children, aria-required-parent, select-name
                const label = ($grid.data('a11y-label') || document.title || 'Data table').toString().trim();

                const $gview = $('#gview_jqGridTable');
                if ($gview.length) {
                    // jqGrid uses role="grid" but its internal structure doesn't satisfy required children roles
                    $gview.attr('role', 'region');
                    $gview.attr('aria-label', label);
                    $gview.removeAttr('aria-multiselectable');
                }

                // jqGrid header divs have role="columnheader" but aren't inside required role="row"
                $('[id^="jqgh_jqGridTable_"]').removeAttr('role');

                // Ensure column headers have proper table scopes (axe/pa11y: th-has-scope).
                // jqGrid may render headers in a separate header table under #gview_jqGridTable,
                // and can rebuild them after loadComplete (resize/remap/frozen columns/etc).
                const applyHeaderScopes = function () {
                    const $root = $('#gview_jqGridTable');
                    if (!$root.length) return;

                    $root.find('th').each(function () {
                        const $th = $(this);

                        // Only set for actual jqGrid column header cells (be conservative).
                        const id = ($th.attr('id') || '').toString();
                        const isJqGridHeader =
                            $th.hasClass('ui-th-column') ||
                            id.startsWith('jqGridTable_') ||
                            id.startsWith('jqgh_jqGridTable_');

                        if (!isJqGridHeader) return;

                        if (!$th.attr('scope')) {
                            $th.attr('scope', 'col');
                        }
                    });
                };

                applyHeaderScopes();
                setTimeout(applyHeaderScopes, 0);

                // filterToolbar selects need an accessible name
                $('select[id^="gs_jqGridTable_"]').each(function () {
                    const $el = $(this);
                    if (($el.attr('aria-label') || '').trim()) return;

                    const raw = ($el.attr('name') || $el.attr('id') || 'filter').toString();
                    const pretty = raw.replace(/^gs_jqGridTable_/, '').replace(/_/g, ' ').trim();
                    $el.attr('aria-label', `Filter ${pretty}`);
                });

                // Give multiselect checkboxes an accessible name (Axe: label)
                const $selectAll = $('#cb_jqGridTable');
                if ($selectAll.length) {
                    const hasName =
                        (($selectAll.attr('aria-label') || '').trim()) ||
                        (($selectAll.attr('aria-labelledby') || '').trim()) ||
                        (($selectAll.attr('title') || '').trim());
                    if (!hasName) {
                        $selectAll.attr('aria-label', `Select all rows in ${label}`);
                    }
                }

                $('input[type="checkbox"][id^="jqg_jqGridTable_"]').each(function () {
                    const $cb = $(this);

                    const hasName =
                        (($cb.attr('aria-label') || '').trim()) ||
                        (($cb.attr('aria-labelledby') || '').trim()) ||
                        (($cb.attr('title') || '').trim());
                    if (hasName) return;

                    const rowId = $cb.closest('tr.jqgrow').attr('id') || $cb.attr('id') || 'row';
                    $cb.attr('aria-label', `Select row ${rowId} in ${label}`);
                });
            },
            firstLoad = true;

        myColumnsState = restoreColumnState(cm);
        isColState = typeof (myColumnsState) !== 'undefined' && myColumnsState !== null;

        $grid.jqGrid({
            jsonReader: {
                repeatitems: false,
                root: "rows",
                page: "page",
                total: "total",
                records: "records",
                cell: "",
                id: "id"
            },
            url: dataUrl,
            mtype: "GET",
            datatype: "json",
            colNames: cn,
            colModel: cm,
            cmTemplate: {autoResizable: true, editable: true},
            guiStyle: "bootstrap4",
            iconSet: "fontAwesome",
            rowNum: 25,
            page: isColState ? myColumnsState.page : 1,
            search: isColState ? myColumnsState.search : false,
            postData: isColState ? {filters: myColumnsState.filters} : {},
            sortname: isColState ? myColumnsState.sortname : 'id',
            sortorder: isColState ? myColumnsState.sortorder : 'desc',
            autoResizing: {compact: true},
            autoWidthColumns: true,
            autowidth: true,
            rowList: [25, 50, 100, 500, 1000],
            viewrecords: true,
            autoencode: true,
            sortable: true,
            toppager: true,
            pager: true,
            multiselect: checkbox,
            selarrrow: selected,
            searching: {searchOnEnter: true, searchOperators: true},
            onSelectRow: function (id, isSelected) {
                updateIdsOfSelectedRows(id, isSelected);
                setSelectedCount();
            },
            onSelectAll: function (rowIds, isSelected) {
                let i, count, id;
                for (i = 0, count = rowIds.length; i < count; i++) {
                    id = rowIds[i];
                    updateIdsOfSelectedRows(id, isSelected);
                }
                setSelectedCount();
            },
            beforeSelectRow: function (rowid, e) {
                // Prevent row selection if clicking on the OCR cell
                const $td = $(e.target).closest('td');
                const desc = $td.attr('aria-describedby') || '';

                return !($td.hasClass('ocr-clickable') || desc.endsWith('_ocr_text'));
            },
            loadComplete: function () {
                if (firstLoad) {
                    firstLoad = false;
                    if (isColState) {
                        $(this).jqGrid("remapColumns", myColumnsState.permutation, true);
                    }
                }
                saveColumnState.call($(this), this.p.remapColumns);
                setSelected();
                setSelectedCount();

                applyJqGridA11yFixes();
            },
            onCellSelect: function (rowid, iCol, cellcontent, e) {
                const $td = $(e.target).closest('td');
                // Only trigger if the cell matches your OCR criteria
                if ($td.hasClass('ocr-clickable') || ($td.attr('aria-describedby') && $td.attr('aria-describedby').endsWith('_ocr_text'))) {
                    const $span = $(e.target).closest('.ui-jqgrid-cell-wrapper');
                    const text = $span.length ? $span.text() : cellcontent;
                    Livewire.dispatch('openOcrModal', {cellContent: text});
                }
            }
        })
            .jqGrid("navGrid", {add: false, edit: false, del: false, search: true}, {}, {}, {}, {
                afterShowSearch: function ($form) {
                    $form.closest(".ui-jqdialog").position({
                        of: window, // or any other element
                        my: "center center",
                        at: "center center"
                    });
                },
                width: 700,
                multipleSearch: true,
                recreateFilter: true
            })
            .jqGrid("navButtonAdd", {
                caption: '',
                buttonicon: "fas fa-columns",
                title: "Choose columns",
                onClickButton: columnChooser
            })
            .jqGrid("navButtonAdd", {
                caption: '',
                buttonicon: "fas fa-eraser",
                title: "Clear saved grid's settings",
                onClickButton: eraseSettings
            })
            .jqGrid("navButtonAdd", {
                caption: '',
                buttonicon: "fas fa-file-export",
                title: "Export to CSV",
                onClickButton: exportSettings
            })
            .jqGrid("filterToolbar")
            .jqGrid("gridResize");

        applyJqGridA11yFixes();

        $('#gridForm').submit(function () {
            if (selected.length > maxCount) {
                $('#max').addClass('red');
                return false;
            }

            // clear any form settings on submit
            eraseSettings();

            $('#subject-ids').val(selected);
        });
    }
});


/**
 * Map formatter
 *
 * @param column
 */
function mapFormatter(column) {
    let functionsMapping = {
        "imagePreview": function (cellValue, options, rowObject) {
            let url = encodeURIComponent(cellValue);

            const rowId = (options && options.rowId) ? String(options.rowId).trim() : '';
            const context = rowId ? ` (row ${rowId})` : '';
            const safeUrlText = (cellValue || '').toString().trim();

            const viewImageAria = `View image ${safeUrlText}${context}`;
            const viewThumbAria = `View thumbnail ${safeUrlText}${context}`;
            const viewUrlAria = `View image URL ${safeUrlText}${context}`;

            return '<a href="' + cellValue + '" target="_blank" rel="noopener noreferrer" aria-label="' + viewImageAria + '">View Image</a>&nbsp;&nbsp;'
                + '<a href="#" class="thumb-view" data-url="' + Laravel.imagePreviewPath + url + '" data-toggle="modal" data-dismiss="modal" data-toggle="modal" data-size="modal-lg" data-target="#global-modal" data-hover="tooltip" data-title="Preview Thumbnail" title="Preview Thumbnail" aria-label="' + viewThumbAria + '">View Thumb</a>&nbsp;&nbsp;'
                + '<a href="#" class="url-view" data-url="' + Laravel.imagePreviewPath + cellValue + '&url-view=true" data-toggle="modal" data-dismiss="modal" data-toggle="modal" data-size="modal-lg" data-target="#global-modal" data-hover="tooltip" data-title="View URL" title="Preview URL" aria-label="' + viewUrlAria + '">View URL</a>';
        }
    };

    for (let i = 0; i < column.length; i++) {
        let col = column[i];
        if (col.hasOwnProperty("formatter") &&
            functionsMapping.hasOwnProperty(col.formatter)) {
            col.formatter = functionsMapping[col.formatter];
        }
    }
}

/**
 * Display bootbox message.
 *
 * @param title
 * @param message
 */
function bootboxMsg(title, message) {
    bootbox.alert({
        title: title,
        message: message
    })
}
