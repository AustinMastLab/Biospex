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

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use App\Filament\Resources\ApiTokens\Pages\CreateApiToken;
use App\Filament\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\Group;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('s3');
});

function actingAsApiTokenAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    test()->actingAs($admin);

    return $admin;
}

it('refuses the API Tokens page to users who are not admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(ApiTokenResource::getUrl('index'))
        ->assertForbidden();
});

it('lists tokens with their owner and abilities', function () {
    actingAsApiTokenAdmin();
    $owner = User::factory()->create();
    $token = $owner->createToken('WeDigBio Reports', ['wedigbio-dashboard:read'])->accessToken;

    Livewire::test(ListApiTokens::class)
        ->assertCanSeeTableRecords([$token])
        ->assertSee($owner->email)
        ->assertSee('wedigbio-dashboard:read');
});

it('creates a working token with only the chosen abilities and shows it once', function () {
    actingAsApiTokenAdmin();
    $owner = User::factory()->create();

    Livewire::test(CreateApiToken::class)
        ->fillForm([
            'user_id' => $owner->id,
            'name' => 'Public data client',
            'abilities' => ['public:read'],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ApiTokenResource::getUrl('index'));

    $token = PersonalAccessToken::sole();
    expect($token->tokenable->is($owner))->toBeTrue()
        ->and($token->name)->toBe('Public data client')
        ->and($token->abilities)->toBe(['public:read'])
        ->and($token->expires_at)->toBeNull();

    $plainTextToken = session(ListApiTokens::PLAIN_TEXT_TOKEN_SESSION_KEY);

    // The page opens its default action from the browser, so mount it by hand here.
    Livewire::test(ListApiTokens::class)
        ->assertSet('defaultAction', 'showPlainTextToken')
        ->mountAction('showPlainTextToken')
        ->assertActionDataSet(['token' => $plainTextToken]);

    Livewire::test(ListApiTokens::class)
        ->assertSet('defaultAction', null)
        ->assertActionHidden('showPlainTextToken')
        ->assertDontSee($plainTextToken);

    // Drop the admin's session login so the API sees only the bearer token.
    $this->app['auth']->forgetGuards();

    $this->withToken($plainTextToken)->getJson(route('api.v1.projects.index'))->assertOk();
    $this->withToken($plainTextToken)->getJson(route('api.v1.wedigbio-dashboard.index'))->assertForbidden();
});

it('saves the expiry date when one is given', function () {
    actingAsApiTokenAdmin();
    $expiresAt = now()->addMonth()->startOfMinute();

    Livewire::test(CreateApiToken::class)
        ->fillForm([
            'user_id' => User::factory()->create()->id,
            'name' => 'Short-lived',
            'abilities' => ['public:read'],
            'expires_at' => $expiresAt->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PersonalAccessToken::sole()->expires_at->equalTo($expiresAt))->toBeTrue();
});

it('rejects a token without a known ability', function (array $abilities, string $errorKey) {
    actingAsApiTokenAdmin();

    Livewire::test(CreateApiToken::class)
        ->fillForm([
            'user_id' => User::factory()->create()->id,
            'name' => 'No abilities',
            'abilities' => $abilities,
        ])
        ->call('create')
        ->assertHasFormErrors([$errorKey]);

    expect(PersonalAccessToken::count())->toBe(0);
})->with([
    'none' => [[], 'abilities'],
    'unknown' => [['*'], 'abilities.0'],
]);

it('revokes a token', function () {
    actingAsApiTokenAdmin();
    $token = User::factory()->create()->createToken('Old token', ['*'])->accessToken;

    Livewire::test(ListApiTokens::class)
        ->callAction(TestAction::make('delete')->table($token))
        ->assertNotified('Token revoked');

    $this->assertModelMissing($token);
});
