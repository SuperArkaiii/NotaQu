<?php

namespace App\Filament\Resources\NotaItemResource\Pages;

use App\Filament\Resources\NotaItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotaItem extends EditRecord
{
    protected static string $resource = NotaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
