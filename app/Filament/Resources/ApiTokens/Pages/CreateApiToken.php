<?php

namespace App\Filament\Resources\ApiTokens\Pages;

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Create the token through Sanctum so it is hashed, and hand the plain text to the list page to show once.
     *
     * @param  array{user_id: int|string, name: string, abilities: list<string>, expires_at: ?string}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $newAccessToken = User::findOrFail($data['user_id'])->createToken(
            $data['name'],
            $data['abilities'],
            filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null,
        );

        session()->flash(ListApiTokens::PLAIN_TEXT_TOKEN_SESSION_KEY, $newAccessToken->plainTextToken);

        return $newAccessToken->accessToken;
    }

    protected function getRedirectUrl(): string
    {
        return ApiTokenResource::getUrl('index');
    }
}
