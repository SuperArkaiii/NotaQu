<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

protected static ?string $navigationIcon = 'heroicon-o-cube'; // atau ganti sesuai kebutuhan
    protected static ?string $navigationLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            TextInput::make('nama_produk')
                ->required(),

            TextInput::make('stok')
                ->numeric()
                ->required(),

            TextInput::make('harga')
                ->numeric()
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('nama_produk')->label('Nama Produk')->searchable(),
            TextColumn::make('stok')->label('Stok')->sortable(),
            TextColumn::make('harga')->label('Harga')->money('idr')->sortable(),

            BadgeColumn::make('status')
                ->colors([
                    'success' => 'Tersedia',
                    'danger' => 'Habis',
                ])
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {   
        return auth()->user()->can('view_any_product');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_product');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_product');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_product');
    }


}
