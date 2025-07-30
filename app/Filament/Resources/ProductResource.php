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

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart'; // atau ganti sesuai kebutuhan
    protected static ?string $navigationLabel = 'Produk';
    protected static ?string $label = 'Produk';
    protected static ?string $pluralLabel = 'Produk';    

    protected static ?int $navigationSort = 4;

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

                Forms\Components\Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('permintaan_stok')
                ->label('Permintaan Stok')
                ->numeric()
                ->default(0),
                    
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('nama_produk')->label('Nama Produk')->searchable(),
            Tables\Columns\TextColumn::make('category.name')
                ->label('Kategori')
                ->sortable()
                ->searchable(),

            TextColumn::make('stok')->label('Stok')->sortable(),
            
            Tables\Columns\TextColumn::make('permintaan_stok')
                ->label('Permintaan Stok')
                ->color(fn ($record) => $record->permintaan_stok > $record->stok ? 'danger' : 'success'),


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
                
                //---
                Tables\Actions\Action::make('manajemenStok')
                ->label('Manajemen Stok')
                ->form([
                    Forms\Components\Select::make('tipe')
                    ->options([
                        'tambah' => 'Tambah Stok',
                        //'kurang' => 'Kurangi Stok', (belum dipakai)
                    ])
                        ->required(),
                    Forms\Components\TextInput::make('jumlah')
                        ->numeric()
                        ->required()
                        ->label('Jumlah'),
                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')->rows(2),
                        
                ])
                ->action(function ($record, array $data) {
                    $user = auth()->user();

                    if ($data['tipe'] === 'tambah') {
                        $record->stok += $data['jumlah'];
                    } else {
                        if ($record->stok >= $data['jumlah']) {
                        $record->stok -= $data['jumlah'];
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Stok tidak mencukupi!')
                            ->danger()
                            ->send();
                    return;
                }
            }
                    $record->save();

            // simpan riwayat
            \App\Models\StockHistory::create([
                'product_id' => $record->id,
                'user_id' => $user->id,
                'jumlah' => $data['jumlah'],
                'tipe' => $data['tipe'],
                'keterangan' => $data['keterangan'],
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Stok berhasil diperbarui!')
                ->success()
                ->send();
        }),
    

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
