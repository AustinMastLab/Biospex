<?php

namespace App\Filament\Resources\ExportQueues\Schemas;

use App\Enums\ExportQueueStage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExportQueueForm
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
                Select::make('stage')
                    ->options(ExportQueueStage::class)
                    ->required()
                    ->default(ExportQueueStage::Waiting),
                Toggle::make('queued')
                    ->required(),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                Toggle::make('error')
                    ->required(),
            ]);
    }
}
