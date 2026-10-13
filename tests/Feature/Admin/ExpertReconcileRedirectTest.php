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

use App\Models\Expedition;
use App\Models\User;
use App\Services\Reconcile\ExpertReconcileService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->expedition = Expedition::factory()->create();
    $this->user = User::factory()->verified()->create();
});

/**
 * The flash message the response hands to the page. On GET requests FlashHelperMessage moves it from the session
 * into the app_flash cookie.
 *
 * @return array{type: string, message: string}|null
 */
function flashMessage(TestResponse $response): ?array
{
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie): bool => $cookie->getName() === 'app_flash');

    return $cookie ? collect(json_decode($cookie->getValue(), true))->only(['type', 'message'])->all() : null;
}

it('sends a group member back to the expedition when there is no reconcile data', function () {
    $this->user->groups()->attach($this->expedition->project->group);
    $this->mock(ExpertReconcileService::class)
        ->shouldReceive('getPagination')
        ->andReturn(new LengthAwarePaginator([], 0, 1));

    $response = $this->actingAs($this->user)->get(route('admin.reconciles.index', $this->expedition));

    $response->assertRedirect(route('admin.expeditions.show', $this->expedition));
    expect(flashMessage($response))->toBe(['type' => 'danger', 'message' => t('Reconcile data for processing is missing.')]);
});

it('sends a user outside the project group back to the expedition', function (string $route) {
    $response = $this->actingAs($this->user)->get(route($route, $this->expedition));

    $response->assertRedirect(route('admin.expeditions.show', $this->expedition));
    expect(flashMessage($response))->toBe(['type' => 'warning', 'message' => t('You do not have sufficient permissions.')]);
})->with([
    'expert review' => 'admin.reconciles.index',
    'starting expert review' => 'admin.reconciles.create',
]);
