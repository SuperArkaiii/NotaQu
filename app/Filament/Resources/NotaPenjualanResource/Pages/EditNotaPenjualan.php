<?php

namespace App\Filament\Resources\NotaPenjualanResource\Pages;

use App\Filament\Resources\NotaPenjualanResource;
use App\Models\Product;
use Filament\Resources\Pages\EditRecord;

class EditNotaPenjualan extends EditRecord
{
    protected static string $resource = NotaPenjualanResource::class;

    protected function beforeSave(): void
    {
        $nota = $this->record->fresh(); // ambil data lama dari DB

        // Tambahkan kembali stok lama
        foreach ($nota->items as $item) {
            $product = Product::find($item->product_id);

            if ($product) {
                $product->increment('stok', $item->quantity);
            }
        }
    }

    protected function afterSave(): void
    {
        $nota = $this->record;

        // Kurangi stok berdasarkan item baru
        foreach ($nota->items as $item) {
            $product = Product::find($item->product_id);

            if ($product) {
                $product->decrement('stok', $item->quantity);
            }
        }
    }
}
