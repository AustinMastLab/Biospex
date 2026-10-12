<?php

namespace App\Filament\Resources\OcrQueues\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OcrQueueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project.title')
                    ->label('Project'),
                TextEntry::make('expedition_id')
                    ->numeric(thousandsSeparator: '')
                    ->placeholder('-'),
                TextEntry::make('total')
                    ->numeric(thousandsSeparator: ''),
                TextEntry::make('stage'),
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
