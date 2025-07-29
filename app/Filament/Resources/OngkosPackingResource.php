<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OngkosPackingResource\Pages;
use App\Filament\Resources\OngkosPackingResource\RelationManagers;
use App\Models\OngkosPacking;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn as ColumnsTextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;


class OngkosPackingResource extends Resource
{
    protected static ?string $model = OngkosPacking::class;

protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Harga Packing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            TextInput::make('jumlah_koli')
                ->label('Jumlah Koli')
                ->numeric()
                ->minValue(0) // Minimal 0 koli
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('harga', $state * 100000)
                ),

            TextInput::make('harga')
                ->label('Harga')
                ->numeric()
                ->disabled() // User tidak bisa ubah manual
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            ColumnsTextColumn::make('jumlah_koli')
                ->label('Jumlah Koli')
                ->sortable()
                ->searchable(),

            ColumnsTextColumn::make('harga')
                ->label('Harga')
                ->money('idr') // format ke Rupiah
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
            'index' => Pages\ListOngkosPackings::route('/'),
            'create' => Pages\CreateOngkosPacking::route('/create'),
            'edit' => Pages\EditOngkosPacking::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_ongkos::packing');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_ongkos::packing');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_ongkos::packing');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_ongkos::packing');
    }


}
