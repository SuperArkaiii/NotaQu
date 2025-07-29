<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaPenjualanResource\Pages;
use App\Models\NotaPenjualan;
use App\Models\DataPelanggan;
use App\Models\Product;
use App\Models\OngkosPacking;
use App\Exports\NotaGabunganExport;
use App\Exports\NotaGabungan2Export;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class NotaPenjualanResource extends Resource
{
    protected static ?string $model = NotaPenjualan::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoice & Surat Jalan';
    protected static ?string $label = 'Invoice & Surat Jalan';
    protected static ?string $pluralLabel = 'Invoice & Surat Jalan';    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            self::getMainInfoSection(),
            self::getProductItemsSection(),
            self::getCalculationSection(),
        ]);
    }

    private static function getMainInfoSection(): Forms\Components\Card
    {
        return Forms\Components\Card::make([
            Forms\Components\TextInput::make('kode_faktur')
                ->required(),

            Forms\Components\Select::make('data_pelanggan_id')
                ->label('Nama Perusahaan')
                ->relationship('dataPelanggan', 'nama')
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $pelanggan = DataPelanggan::find($state);
                    $set('alamat', $pelanggan?->alamat ?? '');
                }),

            Forms\Components\TextInput::make('alamat')
                ->label('Alamat')
                ->disabled()
                ->dehydrated(false)
                ->reactive()
                ->afterStateHydrated(fn ($set, $record) => 
                    $set('alamat', $record->dataPelanggan->alamat ?? '')
                ),

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

            Forms\Components\Textarea::make('keterangan')
                ->label('Keterangan'),

            Forms\Components\Select::make('ongkos_packing_id')
                ->label('Jumlah Koli')
                ->options(OngkosPacking::all()->pluck('jumlah_koli', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                    $ongkos = OngkosPacking::find($state);

                    // Pastikan ongkos ditemukan
                    if ($ongkos) {
                        $biayaPacking = (int) $ongkos->jumlah_koli * 100000;
                        $set('biaya_packing', $biayaPacking);
                    } else {
                        $set('biaya_packing', 0);
                    }

                    $items = $get('items') ?? [];
                    \App\Filament\Resources\NotaPenjualanResource::updateTotalCalculations($set, $items, $get);
                })
            
        ])
        ->columns(2)
        ->columnSpanFull()
        ->label('Informasi Utama');
    }

    private static function getProductItemsSection(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('items')
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
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $product = Product::find($state);
                            $set('harga', $product?->harga ?? 0);
                            self::handleItemUpdate($set, $get);
                        }),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Jumlah')                        
                        ->numeric()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            self::handleQuantityUpdate($state, $set, $get);
                        }),

                    Forms\Components\TextInput::make('harga')
                        ->label('Harga')
                        ->readOnly()
                        ->suffix('IDR')
                        ->numeric()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            self::handleItemUpdate($set, $get);
                        }),

                    Forms\Components\TextInput::make('diskon')
                        ->label('Diskon (%)')
                        ->default(0)
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            self::handleItemUpdate($set, $get);
                        }),

                    Forms\Components\TextInput::make('pajak')
                        ->label('Pajak (%)')
                        ->default(0)
                        ->reactive()
                        ->numeric()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            self::handleItemUpdate($set, $get);
                        }),

                    Forms\Components\TextInput::make('satuan')
                        ->label('Satuan')
                        ->required(),
                    
                    Forms\Components\Textarea::make('keterangan_produk')
                        ->label('Keterangan Produk'),
                    
                    Forms\Components\TextInput::make('jumlah')
                        ->label('Jumlah')
                        ->numeric()
                        ->readOnly()
                        ->reactive()
                        ->dehydrated()
                        ->suffix('IDR'),

                ])
                ->columns(2)
                ->columnSpanFull(),
            ])
            ->grid(2)
            ->defaultItems(1)
            ->createItemButtonLabel('Tambah Produk')
            ->afterStateUpdated(function (callable $set, $state, Forms\Get $get) {
                self::updateTotalCalculations($set, $state, $get);
            })
            ->columnSpanFull();
    }

    private static function getCalculationSection(): Forms\Components\Card
    {
        return Forms\Components\Card::make([
            Forms\Components\TextInput::make('subtotal')
                ->label('Subtotal')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->suffix('IDR')
                ->reactive(),

            Forms\Components\TextInput::make('dpp')
                ->label('DPP Nilai Lain')
                ->numeric()
                ->readOnly()
                ->dehydrated() // Tidak dikirim saat submit
                ->suffix('IDR')
                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.'))
                ->reactive()
                ->afterStateHydrated(function (callable $set, Forms\Get $get) {
                    $items = $get('items') ?? [];
                    \App\Filament\Resources\NotaPenjualanResource::updateTotalCalculations($set, $items, $get);
                }),

            Forms\Components\TextInput::make('ppn')
                ->label('PPN (12%)')
                ->readOnly()
                ->numeric()
                ->default(0)
                ->dehydrated()
                ->suffix('IDR')
                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.'))
                ->afterStateHydrated(function (callable $set, Forms\Get $get) {
                    $items = $get('items') ?? [];
                    self::updateTotalCalculations($set, $items, $get);
                }),

            Forms\Components\TextInput::make('biaya_packing')
                ->label('Biaya Packing')
                ->numeric()
                ->dehydrated(fn ($state) => true)
                ->readOnly()
                ->suffix('IDR')
                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.'))
                ->reactive()
                ->afterStateHydrated(function (callable $set, Forms\Get $get) {
                    $items = $get('items') ?? [];
                    \App\Filament\Resources\NotaPenjualanResource::updateTotalCalculations($set, $items, $get);
                }),

            Forms\Components\TextInput::make('biaya_kirim')
                ->label('Biaya Kirim')
                ->numeric()
                ->required()
                ->suffix('IDR')
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                    $items = $get('items') ?? [];
                    self::updateTotalCalculations($set, $items, $get);
                }),

            Forms\Components\TextInput::make('total')
                ->label('TOTAL')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->suffix('IDR')
                ->extraAttributes(['class' => 'font-bold text-lg'])
                ->reactive(),
        ])
        ->columns(2)
        ->columnSpanFull()
        ->label('Perhitungan Total');
    }
    
        private static function handleItemUpdate(callable $set, $get): void
        {
            $harga = (float) ($get('harga') ?? 0);
            $qty = (int) ($get('quantity') ?? 0);
            $diskonPersen = (float) ($get('diskon') ?? 0); // tetap dianggap persen
            $pajakPersen = (float) ($get('pajak') ?? 0);

            $subtotalItem = $harga * $qty;

            // Diskon dihitung sebagai persen dari subtotal
            $nilaiDiskon = $diskonPersen > 0 ? $subtotalItem * ($diskonPersen / 100) : 0;

            $nilaiSetelahDiskon = $subtotalItem - $nilaiDiskon;

            // Pajak tetap persen
            $nilaiPajak = $pajakPersen > 0 ? $nilaiSetelahDiskon * ($pajakPersen / 100) : 0;

            $jumlah = $nilaiSetelahDiskon + $nilaiPajak;

            $set('jumlah', $jumlah);
    }

    private static function updateTotalCalculations(callable $set, $state, Forms\Get $get = null): void
    {
        $subtotal = 0;

        foreach ($state as $item) {
            $harga = (float) ($item['harga'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $pajakPersen = (float) ($item['pajak'] ?? 0);

            $itemSubtotal = $harga * $qty;
            $diskonPersen = (float) ($item['diskon'] ?? 0);
            $itemDiskon = $diskonPersen > 0 ? $itemSubtotal * ($diskonPersen / 100) : 0;
            $afterDiskon = $itemSubtotal - $itemDiskon;
            $itemPajak = $pajakPersen > 0 ? $afterDiskon * ($pajakPersen / 100) : 0;

            $itemJumlah = $afterDiskon + $itemPajak;
            $subtotal += $itemJumlah;
        }

        // Simpan subtotal
        $set('subtotal', $subtotal);

        // Hitung DPP (11/12 dari subtotal)
        $dpp = round($subtotal * (11 / 12), 2);
        $set('dpp', $dpp);

        // Hitung PPN (12% dari DPP)
        $ppn = round($dpp * 0.12, 2);
        $set('ppn', $ppn);

        // Ambil biaya packing dan kirim dari form
        $biayaPacking = (float) ($get('biaya_packing') ?? 0);
        $biayaKirim = (float) ($get('biaya_kirim') ?? 0);

        // Hitung total akhir
        $total = round($subtotal + $ppn + $biayaPacking + $biayaKirim, 2);
        $set('total', $total);
    }

    private static function handleQuantityUpdate($state, callable $set, $get): void
    {
        $product = Product::find($get('product_id'));
        
        // Validasi stok
        if ($product && $state > $product->stok) {
            $set('quantity', $product->stok);
            \Filament\Notifications\Notification::make()
                ->title('Stok tidak cukup')
                ->body("Stok tersedia hanya {$product->stok}")
                ->danger()
                ->send();
        }

        self::handleItemUpdate($set, $get);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_faktur')
                    ->label('Kode Faktur')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('Kode PO')
                    ->searchable(),    

                Tables\Columns\TextColumn::make('dataPelanggan.nama')
                    ->label('Nama Perusahaan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->date(),

                Tables\Columns\TextColumn::make('jatuh_tempo')
                    ->date(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Dibuat'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                
                BulkAction::make('exportGabungan')
                    ->label('Surat Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => 
                        (new NotaGabunganExport($records))->download()
                    ),

                BulkAction::make('exportAntar')
                    ->label('Surat Jalan')
                    ->icon('heroicon-o-document-chart-bar')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => 
                        (new NotaGabungan2Export($records))->download()
                    ),
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

    // Authorization methods
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_nota::penjualan');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_nota::penjualan');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_nota::penjualan');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_nota::penjualan');
    }
}