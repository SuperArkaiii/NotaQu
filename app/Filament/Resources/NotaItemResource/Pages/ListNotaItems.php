<?php

namespace App\Filament\Resources\NotaItemResource\Pages;

use App\Filament\Resources\NotaItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaItems extends ListRecords
{
    protected static string $resource = NotaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
