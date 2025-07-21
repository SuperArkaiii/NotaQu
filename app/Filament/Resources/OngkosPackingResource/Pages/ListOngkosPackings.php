<?php

namespace App\Filament\Resources\OngkosPackingResource\Pages;

use App\Filament\Resources\OngkosPackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOngkosPackings extends ListRecords
{
    protected static string $resource = OngkosPackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
