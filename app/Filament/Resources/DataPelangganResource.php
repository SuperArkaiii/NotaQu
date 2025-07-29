<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataPelangganResource\Pages;
use App\Filament\Resources\DataPelangganResource\RelationManagers;
use App\Models\DataPelanggan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;


class DataPelangganResource extends Resource
{
    protected static ?string $model = DataPelanggan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; // untuk ikon Heroicon
    protected static ?string $navigationLabel = 'Data Pelanggan';
    protected static ?string $label = 'Data Pelanggan';
    protected static ?string $pluralLabel = 'Data Pelanggan';    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                ->required(),
                TextInput::make('alamat')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                ->label('Nama')
                ->searchable(),
                TextColumn::make('alamat')
                ->label('Alamat')
                ->searchable(),
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
            'index' => Pages\ListDataPelanggans::route('/'),
            'create' => Pages\CreateDataPelanggan::route('/create'),
            'edit' => Pages\EditDataPelanggan::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_data::pelanggan');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_data::pelanggan');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_data::pelanggan');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_data::pelanggan');
    }

}
