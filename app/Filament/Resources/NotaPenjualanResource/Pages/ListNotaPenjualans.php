<?php

namespace App\Filament\Resources\NotaPenjualanResource\Pages;

use App\Filament\Resources\NotaPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaPenjualans extends ListRecords
{
    protected static string $resource = NotaPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
