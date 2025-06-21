<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers;
use App\Filament\Admin\Resources\ProductResource\Pages\EditProduct;
use App\Models\Client;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Split::make([
                // LEFT: Order Info
                \Filament\Forms\Components\Grid::make(2)
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client.user', 'name')
                            ->required()
                            ->options(
                                \App\Models\Client::with('user')->get()
                                    ->mapWithKeys(fn($client) => [
                                        $client->id => $client->user?->name ?? 'No User'
                                    ])
                            ),

                        Select::make('sales_id')
                            ->label('Sales')
                            ->options(
                                \App\Models\Sales::with('employee.user')->get()
                                    ->pluck('employee.user.name', 'id')
                            )
                            ->required(),

                        TextInput::make('order_number')
                            ->label('Invoice Number')
                            ->disabled()
                            ->dehydrated(true)
                            ->required()
                            ->default(
                                fn() =>
                                'INV-' . str_pad(\App\Models\Order::query()->max('id') + 1, 5, '0', STR_PAD_LEFT)
                            )
                            ->unique(ignoreRecord: true),

                        Select::make('category')
                            ->options(['SO' => 'Sales Order', 'PO' => 'Purchase Order'])
                            ->required(),

                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true)
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, $state, $get) {
                                $details = $get('orderDetails') ?? [];
                                $set('total', collect($details)->sum('subtotal'));
                            }),

                        \Filament\Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),

                // RIGHT: Order Details
                Repeater::make('orderDetails')
                    ->label('Product Details')
                    ->relationship()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('total', collect($state)->sum('subtotal'));
                    })
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $product = \App\Models\Product::find($state);
                                if ($product) {
                                    $set('product_name', $product->name);
                                    $set('price', $product->price);
                                    $set('subtotal', $product->price); // If no quantity yet
                                }
                            }),

                        TextInput::make('product_name')
                            ->label('Product Name')
                            ->required()
                            ->disabled(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $price = $get('price') ?? 0;
                                $set('subtotal', (float)$state * (float)$price);
                            }),

                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $quantity = $get('quantity') ?? 0;
                                $set('subtotal', (float)$state * (float)$quantity);
                            }),

                        TextInput::make('subtotal')
                            ->numeric()
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->searchable(),
                Tables\Columns\TextColumn::make('client.user.name')->label('Client'),
                Tables\Columns\TextColumn::make('sales.employee.user.name')->label('Sales'),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('total')->money('IDR'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Order $record) => $record->category !== 'PO'),

                Tables\Actions\Action::make('Convert to PO')
                    ->visible(fn(Order $record) => $record->category === 'SO')
                    ->action(function (Order $record) {
                        $record->update([
                            'category' => 'PO',
                            'status' => 'converted_to_po',
                        ]);

                        // Create commission
                        $commissionRate = 0.10; // 10% commission
                        \App\Models\SalesCommissions::updateOrCreate(
                            [
                                'sales_id' => $record->sales_id,
                                'order_id' => $record->id,
                            ],
                            [
                                'amount' => $record->total * $commissionRate,
                            ]
                        );
                    })
                    ->requiresConfirmation()
                    ->label('Convert to PO')
                    ->color('success')
                    ->icon('heroicon-o-arrow-right-circle'),

                Tables\Actions\Action::make('print_invoice')
                    ->label('Print Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn(Order $record) => route('orders.invoice', $record))
                    ->openUrlInNewTab()
                    ->visible(fn(Order $record) => $record->category === 'PO'),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
