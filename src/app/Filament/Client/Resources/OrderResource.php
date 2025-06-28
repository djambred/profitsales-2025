<?php

namespace App\Filament\Client\Resources;

use App\Enums\OrderStatus;
use App\Filament\Client\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderFlow;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('client_id', auth()->user()?->client?->id);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('client');
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $record->client_id === $user?->client?->id;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
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

                    Textarea::make('notes')->columnSpanFull(),
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
                                $product = Product::find($state);
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
                Tables\Columns\TextColumn::make('order_number'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof OrderStatus ? $state->label() : OrderStatus::from($state)->label()),
                Tables\Columns\TextColumn::make('total')->money('IDR'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->visible(function (Order $record) {
                        $user = Auth::user();

                        return $record->status === OrderStatus::Pending
                            && $record->client_id === $user?->client?->id;
                    })
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::Approved]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Approved,
                            'notes' => 'Approved by Client',
                        ]);
                    })
                    ->color('success')
                    ->icon('heroicon-o-check'),

                Action::make('reject')
                    ->label('Reject')
                    ->visible(function (Order $record) {
                        $user = Auth::user();

                        return $record->status === OrderStatus::Pending
                            && $record->client_id === $user?->client?->id;
                    })
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::Rejected]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(), // use user_id for consistency
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Rejected,
                            'notes' => 'Rejected by Client. Call for details.',
                        ]);
                    })
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            //'view' => Pages\ViewOrder::route('/{record}'),
            //'create' => Pages\CreateOrder::route('/create'),
            //'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
