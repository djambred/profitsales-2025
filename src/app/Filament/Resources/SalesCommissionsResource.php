<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesCommissionsResource\Pages;
use App\Filament\Resources\SalesCommissionsResource\RelationManagers;
use App\Models\SalesCommissions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class SalesCommissionsResource extends Resource
{
    protected static ?string $model = SalesCommissions::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentPanel()?->getId();

        // Sales can only see their own commissions
        if ($panel === 'sales' && $user->hasRole('sales')) {
            $query->whereHas('sales', function ($q) use ($user) {
                $q->whereHas('employee', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => $user?->hasRole('super_admin'),
            'sales' => $user?->hasRole('sales'),
            default => true,
        };
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin');
    }



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
