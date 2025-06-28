<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\OrderResource\RelationManagers\OrderFlowsRelationManager;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFlow;
use App\Models\SalesCommissions;
use Filament\Facades\Filament;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        // Super Admin can see all records
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Sales can only see orders they are assigned to
        if ($user->hasRole('sales')) {
            // This assumes your User model has a 'sales' relationship
            // that links to the Sales model. The '?->' is a null-safe
            // operator to prevent errors if the relationship doesn't exist.
            return $query->where('sales_id', $user->employee?->sales?->id);
        }

        // Clients can only see their own orders
        if ($user->hasRole('client')) {
            // Your action visibility logic already confirms this relationship structure
            return $query->where('client_id', $user->client?->id);
        }

        // As a fallback, return an empty query for any other roles
        // to prevent accidental data leakage.
        return $query->whereRaw('1 = 0');
    }
    public static function canAccessRecord(Order $record): bool
    {
        $user = Filament::auth()->user();

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('sales') && $record->sales_id === $user->employee?->sales?->id) {
            return true;
        }

        if ($user->hasRole('client') && $record->client_id === $user->client?->id) {
            return true;
        }

        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user->hasRole('super_admin') ||
            ($user->hasRole('sales') && $record->sales_id === $user->employee?->sales?->id) ||
            ($user->hasRole('client') && $record->client_id === $user->client?->id);
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => $user?->hasRole('super_admin'),
            'sales' => $user?->hasRole('sales'),
            'client' => $user?->hasRole('client'),
            default => false,
        };
    }
    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => $user?->hasRole('super_admin'),
            'sales' => $user?->hasRole('sales'),
            //'client' => $user?->hasRole('client'),
            default => false,
        };
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
                        'reject' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn($state) => $state?->label()),
                Tables\Columns\TextColumn::make('total')->money('IDR'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Order $record) => $record->category !== 'PO'),
                Tables\Actions\Action::make('Convert to PO')
                    ->label('Convert to PO')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Order $record): bool {
                        $user = Filament::auth()->user();

                        return $user?->hasRole('sales') &&
                            $record->category === 'SO' &&
                            $record->status === OrderStatus::Approved;
                    })
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
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Order $record) => Filament::auth()->user()?->hasRole('client') && $record->status === OrderStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => OrderStatus::Approved,
                        ]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Approved,
                            'notes' => 'Order approved by client',
                        ]);
                    }),

                // Reject Action
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Order $record) => Filament::auth()->user()?->hasRole('client') && $record->status === OrderStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => OrderStatus::Rejected,
                        ]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Rejected,
                            'notes' => 'Order rejected by client',
                        ]);
                    }),
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
            OrderFlowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
