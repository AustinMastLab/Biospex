<?php

namespace App\Filament\Resources\ExportQueues\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExportQueueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('expedition.title')
                    ->label('Expedition'),
                TextEntry::make('actor.title')
                    ->label('Actor'),
                TextEntry::make('stage'),
                IconEntry::make('queued')
                    ->boolean(),
                TextEntry::make('total')
                    ->numeric(thousandsSeparator: ''),
                IconEntry::make('error')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
