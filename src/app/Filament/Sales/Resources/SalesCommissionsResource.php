<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\SalesCommissionsResource\Pages;
use App\Filament\Sales\Resources\SalesCommissionsResource\RelationManagers;
use App\Models\SalesCommissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesCommissionsResource extends Resource
{
    protected static ?string $model = SalesCommissions::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('sales');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('sales', function ($query) {
                $query->where('user_id', auth()->id());
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Commission')
                    ->money('IDR'),
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSalesCommissions::route('/'),
            'create' => Pages\CreateSalesCommissions::route('/create'),
            'edit' => Pages\EditSalesCommissions::route('/{record}/edit'),
        ];
    }
}
