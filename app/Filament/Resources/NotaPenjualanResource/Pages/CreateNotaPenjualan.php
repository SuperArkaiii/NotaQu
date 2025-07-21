<?php

namespace App\Filament\Resources\NotaPenjualanResource\Pages;

use App\Filament\Resources\NotaPenjualanResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaPenjualan extends CreateRecord
{
    protected static string $resource = NotaPenjualanResource::class;

    protected function afterCreate(): void
    {
        $nota = $this->record;

        foreach ($nota->items as $item) {
            $product = Product::find($item->product_id);

            if ($product) {
                $product->decrement('stok', $item->quantity);
            }
        }
    }
}
