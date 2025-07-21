<?php

namespace App\Filament\Resources\DataPelangganResource\Pages;

use App\Filament\Resources\DataPelangganResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataPelanggans extends ListRecords
{
    protected static string $resource = DataPelangganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
