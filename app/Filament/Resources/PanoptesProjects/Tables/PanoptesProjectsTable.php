<?php

namespace App\Filament\Resources\PanoptesProjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PanoptesProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_id')
                    ->numeric(thousandsSeparator: '')
                    ->sortable(),
                TextColumn::make('expedition_id')
                    ->numeric(thousandsSeparator: '')
                    ->sortable(),
                TextColumn::make('panoptes_project_id')
                    ->numeric(thousandsSeparator: '')
                    ->sortable(),
                TextColumn::make('panoptes_workflow_id')
                    ->numeric(thousandsSeparator: '')
                    ->sortable()
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('panoptes_workflow_id', $search);
                    }),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
