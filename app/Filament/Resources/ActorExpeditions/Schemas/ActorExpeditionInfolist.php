<?php

namespace App\Filament\Resources\ActorExpeditions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActorExpeditionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('expedition.title')
                    ->label('Expedition'),
                TextEntry::make('actor.title')
                    ->label('Actor'),
                TextEntry::make('state'),
                TextEntry::make('total')
                    ->numeric(thousandsSeparator: ''),
                TextEntry::make('error')
                    ->numeric(thousandsSeparator: ''),
                TextEntry::make('order')
                    ->numeric(thousandsSeparator: ''),
                TextEntry::make('expert')
                    ->numeric(thousandsSeparator: ''),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
