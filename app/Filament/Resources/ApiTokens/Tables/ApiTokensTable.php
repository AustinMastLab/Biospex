<?php

namespace App\Filament\Resources\ApiTokens\Tables;

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApiTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('tokenable'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tokenable.email')
                    ->label('User'),
                TextColumn::make('abilities')
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->dateTime(timezone: ApiTokenResource::adminTimezone())
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime(timezone: ApiTokenResource::adminTimezone())
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime(timezone: ApiTokenResource::adminTimezone())
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API token')
                    ->modalDescription('Anything using this token loses API access immediately. This cannot be undone.')
                    ->modalSubmitActionLabel('Revoke')
                    ->successNotificationTitle('Token revoked'),
            ]);
    }
}
