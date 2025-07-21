<?php

namespace App\Filament\Resources\DataPelangganResource\Pages;

use App\Filament\Resources\DataPelangganResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataPelanggan extends EditRecord
{
    protected static string $resource = DataPelangganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
