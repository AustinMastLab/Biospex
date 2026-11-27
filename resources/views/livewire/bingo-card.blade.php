{{--
    A player's bingo card (App\Livewire\BingoCard). Squares are marked in the browser (Alpine) so play never waits on
    the server; when the marks complete a line, claimWin() checks it on the server. A new card ($deal) starts fresh.
--}}
<div>
    <div id="bingo-rows" class="bingo" wire:key="card-{{ $deal }}"
         x-data="{
            marked: [@js(\App\Livewire\BingoCard::FREE)],
            lines: @js($lines),
            claimed: false,
            toggle(cell) {
                this.marked = this.marked.includes(cell) ? this.marked.filter((id) => id !== cell) : [...this.marked, cell];
                if (! this.claimed && this.lines.some((line) => line.every((id) => this.marked.includes(id)))) {
                    this.claimed = true;
                    $wire.claimWin(this.marked);
                }
            },
         }">
        @foreach($rows as $rowIndex => $row)
            <div class="bingo-row" wire:key="card-{{ $deal }}-row-{{ $rowIndex }}">
                @foreach($row as $cellId => $values)
                    @if ($cellId === \App\Livewire\BingoCard::FREE)
                        <div class="square logo"
                             wire:key="card-{{ $deal }}-{{ $cellId }}">
                            <img src="{{ $project?->present()->show_logo }}"
                                 onerror="this.onerror=null;this.src='{{ config('config.missing_project_logo') }}'"
                                 class="img-fluid" alt="{{ $project?->title }} logo">
                        </div>
                        @continue
                    @endif
                    <button type="button"
                            class="square"
                            id="{{ $cellId }}"
                            wire:key="card-{{ $deal }}-{{ $cellId }}"
                            @click="toggle(@js($cellId))"
                            :class="{ 'clicked': marked.includes(@js($cellId)) }"
                            :aria-pressed="marked.includes(@js($cellId)).toString()"
                            aria-pressed="false"
                            @if(! empty($values[1]))
                                data-hover="tooltip"
                                title="{{ $values[1] }}"
                            @endif
                    >{{ $values[0] }}</button>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
