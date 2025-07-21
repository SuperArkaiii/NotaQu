<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaPenjualanResource\Pages;
use App\Models\NotaPenjualan;
use App\Models\DataPelanggan;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\BulkAction;
use App\Exports\NotaGabunganExport;
use App\Exports\NotaGabungan2Export;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NotaPenjualanResource extends Resource
{
    protected static ?string $model = NotaPenjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Nota Penjualan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make([
                        Forms\Components\TextInput::make('kode_faktur')
                            ->required(),
                        Forms\Components\Select::make('data_pelanggan_id')
                            ->label('Nama Perusahaan')
                            ->relationship('dataPelanggan', 'nama')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $pelanggan = \App\Models\DataPelanggan::find($state);
                                $set('alamat', $pelanggan?->alamat ?? '');
                            }),
                        Forms\Components\TextInput::make('alamat')
                        ->label('Alamat')
                            ->disabled()
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateHydrated(fn ($set, $record) => $set('alamat', $record->dataPelanggan->alamat ?? ''))
                            ->required(false),
                        Forms\Components\TextInput::make('nomor_po')
                            ->label('Nomor PO')
                            ->required(),                       
                        Forms\Components\DatePicker::make('tanggal')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('jatuh_tempo')
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_kirim')
                            ->label('Tanggal Kirim')
                            ->required(),
                        Forms\Components\TextInput::make('biaya_kirim')
                            ->label('Biaya Kirim')
                            ->numeric()
                            ->required(),

                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->label('Informasi Utama'),
                    
 // Repeater produk dengan tampilan kotak per item
            Forms\Components\Repeater::make('items')
                ->relationship()
                ->label('Produk')
                ->schema([
                    Forms\Components\Card::make([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->options(Product::all()->pluck('nama_produk', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('harga', Product::find($state)?->harga ?? 0)
                            ),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                $product = Product::find($get('product_id'));

                                if ($product && $state > $product->stok) {
                                    $set('quantity', $product->stok);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Stok tidak cukup')
                                        ->body("Stok tersedia hanya {$product->stok}")
                                        ->danger()
                                        ->send();
                                }

                                $harga = (int) $get('harga');
                                $qty = (int) $state;
                                $diskon = 0;
                                $jumlah = ($harga * $qty) - $diskon;

                                $set('diskon', $diskon);
                                $set('jumlah', $jumlah);
                            }),

                        Forms\Components\TextInput::make('harga')
                            ->label('Harga')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\TextInput::make('diskon')
                            ->label('Diskon')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah Total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                ])
                ->grid(2) // 3 produk per baris, wrap ke bawah
                ->defaultItems(1)
                ->createItemButtonLabel('Tambah Produk')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_faktur')->label('Kode Faktur'),
                Tables\Columns\TextColumn::make('dataPelanggan.nama')->label('Nama Perusahaan')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('jatuh_tempo')->date(),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Dibuat'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                BulkAction::make('exportGabungan')
                    ->label('Surat Barang')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        return (new NotaGabunganExport($records))->download();
                    })
                    ,
                BulkAction::make('exportAntar')
                    ->label('Surat Kirim')
                    ->icon('heroicon-o-document-chart-bar')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        return (new NotaGabungan2Export($records))->download();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotaPenjualans::route('/'),
            'create' => Pages\CreateNotaPenjualan::route('/create'),
            'edit' => Pages\EditNotaPenjualan::route('/{record}/edit'),
        ];
    }
}