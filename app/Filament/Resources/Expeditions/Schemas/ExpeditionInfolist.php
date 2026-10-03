<?php

namespace App\Filament\Resources\Expeditions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExpeditionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project.title')
                    ->label('Project'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('keywords'),
                TextEntry::make('workflow.title')
                    ->label('Workflow')
                    ->placeholder('-'),
                IconEntry::make('completed')
                    ->boolean(),
                IconEntry::make('locked')
                    ->boolean(),
                ImageEntry::make('logo_display')
                    ->label('Logo')
                    ->height(150)
                    ->width(150)
                    ->getStateUsing(function ($record) {
                        // Custom logic to determine the logo URL
                        if (! empty($record->logo_path)) {
                            return $record->present()->show_logo;
                        }

                        return config('config.missing_expedition_logo');
                    }),
                TextEntry::make('logo_path')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
