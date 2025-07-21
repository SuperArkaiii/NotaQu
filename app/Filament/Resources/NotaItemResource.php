<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\NotaItemResource\Pages;
// use App\Filament\Resources\NotaItemResource\RelationManagers;
// use App\Models\NotaItem;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

// class NotaItemResource extends Resource
// {
//     protected static ?string $model = NotaItem::class;

//     protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
//     protected static ?string $navigationLabel = 'Data Produk per Nota';

//     public static function form(Form $form): Form
//     {
//         return $form->schema([
//             Forms\Components\Select::make('nota_penjualan_id')
//                 ->label('Nota')
//                 ->relationship('nota', 'kode_faktur')
//                 ->required(),

//             Forms\Components\Select::make('product_id')
//                 ->label('Produk')
//                 ->relationship('product', 'nama_produk')
//                 ->required(),

//             Forms\Components\TextInput::make('quantity')->numeric()->required(),
//             Forms\Components\TextInput::make('harga')->numeric()->required(),
//             Forms\Components\TextInput::make('diskon')->numeric()->required(),
//             Forms\Components\TextInput::make('jumlah')->numeric()->required(),
//         ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table->columns([
//             Tables\Columns\TextColumn::make('nota.kode_faktur')->label('Faktur'),
//             Tables\Columns\TextColumn::make('product.nama_produk')->label('Produk'),
//             Tables\Columns\TextColumn::make('quantity'),
//             Tables\Columns\TextColumn::make('harga')->money('IDR'),
//             Tables\Columns\TextColumn::make('diskon')->money('IDR'),
//             Tables\Columns\TextColumn::make('jumlah')->money('IDR'),
//         ])
//         ->filters([])

//         ->actions([
//             Tables\Actions\EditAction::make(),
//             Tables\Actions\DeleteAction::make(),
//         ])

//         ->bulkActions([
//             Tables\Actions\BulkActionGroup::make([
//                 Tables\Actions\DeleteBulkAction::make(),
//             ]),
//         ]);
//     }

//     public static function getRelations(): array
//     {
//         return [];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListNotaItems::route('/'),
//             'create' => Pages\CreateNotaItem::route('/create'),
//             'edit' => Pages\EditNotaItem::route('/{record}/edit'),
//         ];
//     }
// }