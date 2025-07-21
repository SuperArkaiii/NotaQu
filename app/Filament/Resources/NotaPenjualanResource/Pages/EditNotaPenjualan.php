<?php

namespace App\Filament\Resources\NotaPenjualanResource\Pages;

use App\Filament\Resources\NotaPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotaPenjualan extends EditRecord
{
    protected static string $resource = NotaPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
