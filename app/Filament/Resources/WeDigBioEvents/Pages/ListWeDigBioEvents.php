<?php

namespace App\Filament\Resources\WeDigBioEvents\Pages;

use App\Filament\Resources\WeDigBioEvents\WeDigBioEventResource;
use Filament\Resources\Pages\ListRecords;

class ListWeDigBioEvents extends ListRecords
{
    protected static string $resource = WeDigBioEventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
