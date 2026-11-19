<div class="subject-grid w-100">
    @if($started)
        <div class="alert alert-info text-center col-lg-8 mx-auto">
            {{ t('This Expedition has started, so its Subjects can no longer change.') }}
        </div>
    @endif

    @if($selectable)
        {{-- The form posts this token; the chosen subject ids stay on the server (App\Services\Grid\SubjectSelection). --}}
        <input type="hidden" name="subject-selection" value="{{ $selectionToken }}" form="gridForm">
        <div class="alert alert-info text-center col-lg-8 mx-auto">
            {{ $mode === 'edit'
                ? t('Checked rows are the Subjects selected for this Expedition. Saving the form applies your changes.')
                : t('Check the Subjects for this Expedition. Saving the form adds them.') }}
        </div>
        <div class="subject-grid-selection text-center mb-3">
            <p class="mb-2" role="status" aria-live="polite">
                {{ t('Subjects selected') }}:
                <strong @class(['text-danger' => $chosenCount >= $maxCount])>{{ number_format($chosenCount) }}</strong>
                <span @class(['text-danger' => $chosenCount >= $maxCount])>{{ t('(%s max. per Expedition)', number_format($maxCount)) }}</span>
            </p>
            @if($notice !== '')
                <p class="text-danger mb-2" role="alert">{{ $notice }}</p>
            @endif
            @if($rows->total() > $maxCount)
                <p class="small mb-2">{{ t('%s subjects match. Filter them to %s or fewer to select them all at once.', number_format($rows->total()), number_format($maxCount)) }}</p>
            @endif
            <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" wire:click="$set('assigned', '{{ $assigned === 'chosen' ? 'all' : 'chosen' }}')">
                <i class="fas fa-check-square" aria-hidden="true"></i>
                {{ $assigned === 'chosen' ? t('Show all subjects') : t('Show selected subjects') }}
            </button>
            @php($tooManyToSelect = $rows->total() > $maxCount)
            <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" wire:click="selectAllMatching"
                    @disabled($rows->total() === 0 || $tooManyToSelect)
                    @if($tooManyToSelect) title="{{ t('More than %s subjects match. Filter them to select all.', number_format($maxCount)) }}" @endif>
                <i class="fas fa-check-double" aria-hidden="true"></i> {{ t('Select all %s', number_format($rows->total())) }}
            </button>
            <button type="button" class="btn btn-primary btn-sm mb-1" wire:click="clearSelection" @disabled($chosenCount === 0)>
                <i class="fas fa-times" aria-hidden="true"></i> {{ t('Clear selected') }}
            </button>
        </div>
    @endif

    <div class="subject-grid-toolbar d-flex flex-wrap justify-content-between align-items-center mb-2">
        <div class="mb-1">
            <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" data-modal-target="#subject-grid-columns">
                <i class="fas fa-columns" aria-hidden="true"></i> {{ t('Columns') }}
            </button>
            <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" wire:click="clearFilters">
                <i class="fas fa-eraser" aria-hidden="true"></i> {{ t('Clear filters') }}
            </button>
            <button type="button" class="btn btn-primary btn-sm mb-1" wire:click="export">
                <i class="fas fa-file-export" aria-hidden="true"></i> {{ t('Export CSV') }}
            </button>
        </div>
        <label for="subject-grid-per-page" class="mb-1">
            {{ t('Show') }}
            <select id="subject-grid-per-page" class="custom-select custom-select-sm d-inline-block w-auto mx-1"
                    wire:model.live="perPage">
                @foreach(\App\Livewire\Admin\SubjectGrid::PER_PAGE_OPTIONS as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            {{ t('rows') }}
        </label>
    </div>

    <div class="table-responsive subject-grid-scroll" wire:loading.class="subject-grid-loading">
        <table class="table table-striped table-bordered table-sm data-table subject-grid-table mb-0">
            <thead>
            <tr>
                @if($selectable)
                    <th scope="col" class="subject-grid-check">
                        @php($pageChosen = count($pageIds) > 0 && count($chosenOnPage) === count($pageIds))
                        {{-- The state is in the key so Livewire replaces the box when it changes: a clicked checkbox
                             keeps showing its own state otherwise, whatever the server renders. --}}
                        <input type="checkbox" wire:key="page-check-{{ $pageChosen ? 'on' : 'off' }}"
                               aria-label="{{ t('Select every subject on this page') }}"
                               @checked($pageChosen)
                               wire:click="togglePage($event.target.checked)">
                    </th>
                @endif
                @foreach($columns as $column)
                    @if($column === 'assigned')
                        <th scope="col">{{ t('Assigned') }}</th>
                    @else
                        <x-data-table.sort-header :field="$column" :label="$column" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                    @endif
                @endforeach
            </tr>
            <tr class="subject-grid-filters">
                @if($selectable)
                    <td></td>
                @endif
                @foreach($columns as $column)
                    @php($position = array_search($column, $allColumns, true))
                    <td wire:key="filter-{{ $position }}">
                        @if($column === 'assigned')
                            <select class="custom-select custom-select-sm" aria-label="{{ t('Filter by assigned') }}" wire:model.live="assigned">
                                <option value="all">{{ t('All') }}</option>
                                <option value="true">{{ t('Yes') }}</option>
                                <option value="false">{{ t('No') }}</option>
                                @if($mode === 'edit')
                                    <option value="current">{{ t('Saved in this expedition') }}</option>
                                @endif
                                @if($selectable)
                                    <option value="chosen">{{ t('Selected') }}</option>
                                    <option value="unchosen">{{ t('Not selected') }}</option>
                                @endif
                            </select>
                        @elseif($column === 'exported')
                            <select class="custom-select custom-select-sm" aria-label="{{ t('Filter by exported') }}" wire:model.live="exported">
                                <option value="all">{{ t('All') }}</option>
                                <option value="true">{{ t('Yes') }}</option>
                                <option value="false">{{ t('No') }}</option>
                            </select>
                        @elseif($column !== 'accessURI')
                            <input type="search" class="form-control form-control-sm" autocomplete="off"
                                   aria-label="{{ t('Filter %s', $column) }}"
                                   title="{{ t('Matches text anywhere. Use “or” for either and “and” for both, for example: florida or georgia, escambia and beach') }}"
                                   x-on:keydown.enter.prevent
                                   wire:model.live.debounce.500ms="filters.c{{ $position }}">
                        @endif
                    </td>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php($subjectId = $this->rowId($row))
                <tr wire:key="subject-{{ $subjectId }}">
                    @if($selectable)
                        <td class="subject-grid-check">
                            @php($isChosen = in_array($subjectId, $chosenOnPage, true))
                            <input type="checkbox" wire:key="check-{{ $subjectId }}-{{ $isChosen ? 'on' : 'off' }}"
                                   aria-label="{{ t('Select subject %s', $row['imageId'] ?? $subjectId) }}"
                                   @checked($isChosen)
                                   wire:click="toggleSubject('{{ $subjectId }}', $event.target.checked)">
                        </td>
                    @endif
                    @foreach($columns as $column)
                        @php($value = $this->cellValue($row, $column))
                        @if($column === 'accessURI')
                            <td class="text-nowrap">
                                <a href="{{ $value }}" target="_blank" rel="noopener noreferrer">{{ t('View Image') }}</a>
                                <a href="#" class="prevent-default ml-2" data-modal-target="#global-modal" data-size="modal-lg"
                                   data-title="{{ t('Preview Thumbnail') }}"
                                   data-url="{{ route('admin.images.preview', ['url' => $value]) }}">{{ t('View Thumb') }}</a>
                                <a href="#" class="prevent-default ml-2" data-modal-target="#global-modal" data-size="modal-lg"
                                   data-title="{{ t('View URL') }}"
                                   data-url="{{ route('admin.images.preview', ['url' => $value, 'url-view' => 'true']) }}">{{ t('View URL') }}</a>
                            </td>
                        @elseif($column === 'ocr' && $value !== '')
                            <td class="subject-grid-cell">
                                <button type="button" class="subject-grid-ocr" title="{{ t('Show the full OCR text') }}"
                                        wire:click="$dispatch('openOcrModal', { cellContent: @js($value) })">{{ $value }}</button>
                            </td>
                        @else
                            <td class="subject-grid-cell" title="{{ $value }}">{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($selectable ? 1 : 0) }}" class="text-center">{{ t('No matching subjects found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        <x-data-table.footer :paginator="$rows" :label="t('Subject pages')"/>
    </div>

    <x-modal id="subject-grid-columns" :title="t('Columns')" size="modal-xl" wire:ignore.self>
        <div class="px-4 pb-3 text-left">
            <p class="small mb-2">{{ t('Tick a column to show it, untick to hide it. Drag shown columns to change their order.') }}</p>
            <input type="search" class="form-control form-control-sm mb-3" autocomplete="off"
                   placeholder="{{ t('Find a column') }}" aria-label="{{ t('Find a column') }}"
                   wire:model.live.debounce.300ms="columnSearch">

            <h3 class="h6">{{ t('Shown') }}</h3>
            <ul class="list-unstyled subject-grid-column-list" wire:sort="moveColumn">
                @foreach($columns as $column)
                    @if($columnSearch === '' || str_contains(mb_strtolower($column), mb_strtolower($columnSearch)))
                        <li wire:key="shown-{{ $column }}" wire:sort:item="{{ $column }}" class="d-flex align-items-center" title="{{ $column }}">
                            <span wire:sort:handle class="subject-grid-drag mr-2" title="{{ t('Drag to reorder') }}">
                                <i class="fas fa-grip-vertical" aria-hidden="true"></i>
                            </span>
                            <label class="mb-0 text-truncate">
                                <input type="checkbox" checked wire:click="toggleColumn('{{ $column }}')"> {{ $column }}
                            </label>
                        </li>
                    @endif
                @endforeach
            </ul>

            <h3 class="h6 mt-3">{{ t('Hidden') }}</h3>
            <ul class="list-unstyled subject-grid-column-list">
                @foreach(array_diff($allColumns, $columns) as $column)
                    @if($columnSearch === '' || str_contains(mb_strtolower($column), mb_strtolower($columnSearch)))
                        <li wire:key="hidden-{{ $column }}" class="d-flex align-items-center" title="{{ $column }}">
                            <label class="mb-0 text-truncate">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')"> {{ $column }}
                            </label>
                        </li>
                    @endif
                @endforeach
            </ul>

            <button type="button" class="btn btn-outline-primary btn-sm mt-2" wire:click="resetColumns">{{ t('Reset to the default columns') }}</button>
        </div>
    </x-modal>
</div>
