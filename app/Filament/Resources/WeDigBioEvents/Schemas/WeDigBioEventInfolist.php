<?php

namespace App\Filament\Resources\WeDigBioEvents\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WeDigBioEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Name'),
                TextEntry::make('slug'),
                TextEntry::make('start_date')
                    ->label('Start Date')
                    ->dateTime(),
                TextEntry::make('end_date')
                    ->label('End Date')
                    ->dateTime(),
                IconEntry::make('active')
                    ->boolean(),
                IconEntry::make('is_public')
                    ->label('Public')
                    ->boolean(),
                IconEntry::make('is_archived')
                    ->label('Archived')
                    ->boolean(),
            ]);
    }
}
