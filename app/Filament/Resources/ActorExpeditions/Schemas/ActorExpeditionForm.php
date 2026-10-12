<?php

namespace App\Filament\Resources\ActorExpeditions\Schemas;

use App\Enums\ActorExpeditionState;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActorExpeditionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expedition_id')
                    ->relationship('expedition', 'title')
                    ->required(),
                Select::make('actor_id')
                    ->relationship('actor', 'title')
                    ->required(),
                Select::make('state')
                    ->options(ActorExpeditionState::class)
                    ->required()
                    ->default(ActorExpeditionState::NotStarted),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('error')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('expert')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
