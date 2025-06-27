<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\OrderResource\Pages;
use App\Filament\Sales\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\OrderStatus;
use App\Models\OrderFlow;
use App\Models\SalesCommissions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Split;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('sales');
    }
    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('sales');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Split::make([
                Grid::make(2)->schema([
                    Select::make('client_id')
                        ->label('Client')
                        ->relationship('client.user', 'name')
                        ->required()
                        ->options(
                            \App\Models\Client::with('user')->get()
                                ->mapWithKeys(fn($client) => [$client->id => $client->user?->name ?? 'No User'])
                        ),
                    TextInput::make('sales_name')
                        ->default(fn() => auth()->user()?->name)
                        ->disabled()
                        ->dehydrated(false),
                    Hidden::make('sales_id')
                        ->default(fn() => Auth::id())
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

                    \Filament\Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),

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
                                    $set('subtotal', $product->price);
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state?->value ?? $state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'converted_to_po' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn($state) => $state?->label()),
                Tables\Columns\TextColumn::make('total')->money('IDR'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Order $record) => $record->category !== 'PO'),

                Tables\Actions\Action::make('Convert to PO')
                    ->visible(fn(Order $record) => $record->category === 'SO' && $record->status === OrderStatus::Approved)
                    ->action(function (Order $record) {
                        $record->update([
                            'category' => 'PO',
                            'status' => OrderStatus::ConvertedToPO,
                        ]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Approved,
                            'to_status' => OrderStatus::ConvertedToPO,
                            'notes' => 'Converted to Purchase Order by Sales',
                        ]);

                        SalesCommissions::updateOrCreate(
                            [
                                'sales_id' => $record->sales_id,
                                'order_id' => $record->id,
                            ],
                            [
                                'amount' => $record->total * 0.10,
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            //'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    // Prevent editing/deleting
    public static function canDelete($record): bool
    {
        return false;
    }
}
