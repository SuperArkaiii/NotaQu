<?php

namespace App\Filament\Resources\OngkosPackingResource\Pages;

use App\Filament\Resources\OngkosPackingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOngkosPacking extends EditRecord
{
    protected static string $resource = OngkosPackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
