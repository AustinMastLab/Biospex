<?php

namespace App\Filament\Resources\ApiTokens\Schemas;

use App\Enums\ApiAbility;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApiTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->helperText('The account the token acts as.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => User::query()
                        ->where('email', 'like', "%{$search}%")
                        ->orderBy('email')
                        ->limit(50)
                        ->pluck('email', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => User::find($value)?->email)
                    ->required(),
                TextInput::make('name')
                    ->helperText('Who or what uses the token, e.g. "WeDigBio Reports".')
                    ->required()
                    ->maxLength(255),
                CheckboxList::make('abilities')
                    ->options(ApiAbility::options())
                    ->descriptions(ApiAbility::descriptions())
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->helperText('Leave empty for a token that never expires.')
                    ->after('now'),
            ]);
    }
}
