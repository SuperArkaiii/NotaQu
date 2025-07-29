<?php

namespace App\Filament\Widgets;

use App\Models\NotaItem;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class SalesByCategoryTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Penjualan Berdasarkan Kategori';

    public function table(Tables\Table $table): Tables\Table
    {
        $query = NotaItem::query()
            ->join('products', 'nota_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.id as id, categories.name as kategori, SUM(nota_items.quantity) as total_quantity, SUM(nota_items.jumlah) as total_penjualan')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_penjualan', 'desc');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori Produk'),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Jumlah Terjual')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->money('idr')
                    ->sortable(),
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->id; //category id 
    }
}
