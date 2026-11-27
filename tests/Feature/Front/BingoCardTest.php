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

use App\Jobs\BingoJob;
use App\Livewire\BingoCard;
use App\Models\Bingo;
use App\Models\BingoUser;
use App\Models\BingoWord;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Bus::fake([BingoJob::class]);
    Storage::fake('s3');

    $this->bingo = Bingo::factory()->create();
    foreach (range(1, 24) as $number) {
        BingoWord::factory()->for($this->bingo)->create(['word' => 'word'.$number, 'definition' => 'definition '.$number]);
    }
    $this->bingoUser = BingoUser::factory()->for($this->bingo)->create();
});

function bingoCard(): Testable
{
    return Livewire::test(BingoCard::class, ['bingo' => test()->bingo, 'bingoUser' => test()->bingoUser]);
}

it('deals a five by five card of the bingo\'s words with the logo in the centre', function () {
    $card = bingoCard();

    $rows = $card->get('rows');
    expect($rows)->toHaveCount(5)
        ->and(collect($rows)->flatMap(fn (array $row) => array_keys($row))->all())
        ->toBe(['a1', 'b1', 'c1', 'd1', 'e1', 'a2', 'b2', 'c2', 'd2', 'e2', 'a3', 'b3', 'c3', 'd3', 'e3', 'a4', 'b4', 'c4', 'd4', 'e4', 'a5', 'b5', 'c5', 'd5', 'e5']);

    $card->assertSeeHtml('id="a1"')
        ->assertSeeHtml('aria-pressed="false"')
        ->assertDontSeeHtml('id="c3"');
});

it('counts a win when the marked squares make a line, and announces it once per card', function () {
    $card = bingoCard()
        ->call('claimWin', ['a3', 'b3', 'd3', 'e3'])
        ->assertDispatched('bingo-won', message: t('You are a winner!! Congratulations!!'));

    Bus::assertDispatched(BingoJob::class, fn (BingoJob $job) => (fn () => $this->winner)->call($job) === true);

    $card->call('claimWin', ['a1', 'a2', 'a3', 'a4', 'a5']);
    Bus::assertDispatchedTimes(BingoJob::class, 1);
});

it('does not count a claim that makes no line, or that names squares not on the card', function () {
    bingoCard()
        ->call('claimWin', ['a3', 'b3', 'd3'])
        ->call('claimWin', ['a3', 'b3', 'd3', 'z9'])
        ->assertNotDispatched('bingo-won');

    Bus::assertNotDispatched(BingoJob::class);
});

it('lets a player win again on a new card', function () {
    bingoCard()
        ->call('claimWin', ['c1', 'c2', 'c4', 'c5'])
        ->dispatch('bingo-new-card')
        ->assertSet('deal', 2)
        ->call('claimWin', ['c1', 'c2', 'c4', 'c5']);

    Bus::assertDispatchedTimes(BingoJob::class, 2);
});

it('passes other players joining and winning to the page, but not this player\'s own', function () {
    $marker = ['uuid' => 'other-player', 'latitude' => 30.2, 'longitude' => -97.7, 'city' => 'Austin'];

    bingoCard()
        ->call('playerUpdate', ['data' => json_encode(['marker' => $marker, 'winner' => null])])
        ->assertDispatched('bingo-player-joined', marker: $marker)
        ->assertNotDispatched('bingo-won');

    // A win announces the winner without adding their map pin a second time.
    bingoCard()
        ->call('playerUpdate', ['data' => json_encode(['marker' => $marker, 'winner' => ['city' => 'Austin', 'uuid' => 'other-player']])])
        ->assertDispatched('bingo-won', message: t('We Have A Winner In %s!!', 'Austin'))
        ->assertNotDispatched('bingo-player-joined');

    bingoCard()
        ->call('playerUpdate', ['data' => json_encode(['marker' => [...$marker, 'uuid' => test()->bingoUser->uuid], 'winner' => null])])
        ->assertNotDispatched('bingo-player-joined');
});
