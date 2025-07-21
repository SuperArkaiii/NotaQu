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

class DataPelangganResource extends Resource
{
    protected static ?string $model = DataPelanggan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; // untuk ikon Heroicon

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
}
