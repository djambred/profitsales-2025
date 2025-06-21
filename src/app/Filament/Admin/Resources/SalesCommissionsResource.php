<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SalesCommissionsResource\Pages;
use App\Filament\Admin\Resources\SalesCommissionsResource\RelationManagers;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sales_id')
                    ->relationship('sales.employee.user', 'name')
                    ->label('Sales')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->label('Order')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $order = \App\Models\Order::find($state);
                        if ($order) {
                            $set('amount', $order->total * 0.1); // 10% commission logic
                        }
                    }),

                Forms\Components\TextInput::make('amount')
                    ->label('Commission Amount')
                    ->numeric()
                    ->required()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sales.employee.user.name')
                    ->label('Sales')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Commission')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
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
