<?php

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

namespace App\Livewire;

use App\Jobs\BingoJob;
use App\Models\Bingo;
use App\Models\BingoUser;
use App\Services\Bingo\BingoService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * A player's bingo card (front/bingo/card.blade.php).
 *
 * The card's words live on the server; the browser marks squares instantly (Alpine) and asks claimWin() only when a
 * line looks complete. A win counts, and is announced to the other players, only when the marked squares make a line
 * on this card. Players joining and other players' wins arrive as BingoEvent over Reverb.
 */
class BingoCard extends Component
{
    /**
     * The free centre square (the project logo).
     */
    public const FREE = 'c3';

    #[Locked]
    public int $bingoId;

    #[Locked]
    public string $bingoUuid;

    #[Locked]
    public int $bingoUserId;

    #[Locked]
    public string $bingoUserUuid;

    /**
     * The card: five rows of cell id => [word, definition], cell ids a1..e5 (column letter, row number).
     *
     * @var array<int, array<string, array{0: string, 1: string|null}>>
     */
    #[Locked]
    public array $rows = [];

    /**
     * Counts the cards dealt, so a new card replaces the old one's squares and marks.
     */
    #[Locked]
    public int $deal = 0;

    /**
     * The card ($deal) this player last won on, so one card wins (and is announced) only once.
     */
    #[Locked]
    public ?int $wonDeal = null;

    public function mount(Bingo $bingo, BingoUser $bingoUser): void
    {
        $this->bingoId = $bingo->id;
        $this->bingoUuid = $bingo->uuid;
        $this->bingoUserId = $bingoUser->id;
        $this->bingoUserUuid = $bingoUser->uuid;
        $this->newCard();
    }

    /**
     * Deal a new card (after a win, once the winner message closes).
     */
    #[On('bingo-new-card')]
    public function newCard(): void
    {
        $bingo = Bingo::with('words')->findOrFail($this->bingoId);
        $this->rows = app(BingoService::class)->generateBingoCard($bingo)->values()->toArray();
        $this->deal++;
    }

    /**
     * The winning lines: every row, every column and both diagonals, as cell ids.
     *
     * @return array<int, array<int, string>>
     */
    public static function lines(): array
    {
        $columns = ['a', 'b', 'c', 'd', 'e'];
        $numbers = [1, 2, 3, 4, 5];
        $lines = [];

        foreach ($numbers as $number) {
            $lines[] = array_map(fn (string $column) => $column.$number, $columns);
        }

        foreach ($columns as $column) {
            $lines[] = array_map(fn (int $number) => $column.$number, $numbers);
        }

        $lines[] = array_map(fn (int $i) => $columns[$i].$numbers[$i], range(0, 4));
        $lines[] = array_map(fn (int $i) => $columns[$i].$numbers[4 - $i], range(0, 4));

        return $lines;
    }

    /**
     * The browser says the marked squares complete a line. Count the win only if they do on this card.
     *
     * @param  array<int, mixed>  $marked  Cell ids
     */
    public function claimWin(array $marked): void
    {
        $cells = collect($this->rows)->flatMap(fn (array $row) => array_keys($row))->all();
        $marked = array_unique([self::FREE, ...array_intersect(array_map('strval', $marked), $cells)]);

        $won = collect(self::lines())->contains(fn (array $line) => array_diff($line, $marked) === []);

        if (! $won || $this->wonDeal === $this->deal) {
            return;
        }

        $this->wonDeal = $this->deal;
        BingoJob::dispatch(Bingo::findOrFail($this->bingoId), BingoUser::findOrFail($this->bingoUserId), true);
        $this->dispatch('bingo-won', message: t('You are a winner!! Congratulations!!'));
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo:'.config('config.poll_bingo_channel').'.'.$this->bingoUuid.',BingoEvent' => 'playerUpdate',
        ];
    }

    /**
     * Another player joined (their map pin) or won. A win's marker is the winner's existing pin, so it is not added again.
     *
     * @param  array{data?: string}  $event
     */
    public function playerUpdate(array $event): void
    {
        $data = json_decode($event['data'] ?? '', true);
        $marker = $data['marker'] ?? null;

        if (! is_array($marker) || ($marker['uuid'] ?? null) === $this->bingoUserUuid) {
            return;
        }

        if (! empty($data['winner'])) {
            $this->dispatch('bingo-won', message: t('We Have A Winner In %s!!', (string) ($data['winner']['city'] ?? '')));

            return;
        }

        $this->dispatch('bingo-player-joined', marker: $marker);
    }

    public function render(): View
    {
        return view('livewire.bingo-card', [
            'project' => Bingo::with('project')->find($this->bingoId)?->project,
            'lines' => self::lines(),
        ]);
    }
}
