<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\OrderFlowsRelationManager;
use App\Models\Order;
use App\Models\OrderFlow;
use App\Models\SalesCommissions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user->hasRole('super_admin')) return $query;
        if ($user->hasRole('sales')) return $query->where('sales_id', $user->employee?->sales?->id);
        if ($user->hasRole('client')) return $query->where('client_id', $user->client?->id);

        return $query->whereRaw('1 = 0');
    }

    public static function canAccessRecord(Order $record): bool
    {
        $user = Filament::auth()->user();

        return $user->hasRole('super_admin') ||
            ($user->hasRole('sales') && $record->sales_id === $user->employee?->sales?->id) ||
            ($user->hasRole('client') && $record->client_id === $user->client?->id);
    }

    public static function canView(Model $record): bool
    {
        return self::canAccessRecord($record);
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
            'client' => $user?->hasRole('client'),
            default => false,
        };
    }

    public static function form(Form $form): Form
    {
        $user = Filament::auth()->user();
        $role = $user->getRoleNames()->first();
        $branchId = $user->employee?->branch_id ?? $user->client?->branch_id;

        return $form->schema([
            Split::make([
                Grid::make(2)->schema([

                    // Client Picker: only visible for sales
                    Select::make('client_id')
                        ->label('Client')
                        ->options(function () use ($branchId) {
                            return \App\Models\Client::with('user')
                                ->where('branch_id', $branchId)
                                ->get()
                                ->mapWithKeys(fn($client) => [$client->id => $client->user?->name ?? 'No User']);
                        })
                        ->searchable()
                        ->required()
                        ->dehydrated(true)
                        ->visible(fn() => Filament::auth()->user()?->hasRole('sales')),

                    // Hidden client_id for client users
                    Hidden::make('client_id')
                        ->default(fn() => Filament::auth()->user()?->client?->id)
                        ->dehydrated(true)
                        ->visible(fn() => Filament::auth()->user()?->hasRole('client')),

                    // Sales display (for client view)
                    Placeholder::make('sales_display')
                        ->label('Sales')
                        ->content(fn() => Filament::auth()->user()?->employee?->user?->name)
                        ->visible(fn() => Filament::auth()->user()?->hasRole('sales')),

                    // Hidden sales_id for sales users
                    Hidden::make('sales_id')
                        ->default(fn() => Filament::auth()->user()?->employee?->sales?->id)
                        ->dehydrated(true)
                        ->visible(fn() => Filament::auth()->user()?->hasRole('sales')),

                    // Selectable sales_id for client users
                    Select::make('sales_id')
                        ->label('Sales')
                        ->options(function () use ($branchId) {
                            return \App\Models\Sales::with('employee.user')
                                ->whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                                ->get()
                                ->mapWithKeys(fn($sales) => [$sales->id => $sales->employee?->user?->name ?? 'No User']);
                        })
                        ->searchable()
                        ->required()
                        ->dehydrated(true)
                        ->visible(fn() => Filament::auth()->user()?->hasRole('client')),

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
                        ->label('Category')
                        ->options([
                            'SO' => 'Sales Order',
                            'PO' => 'Purchase Order',
                        ])
                        ->default('SO')
                        ->disabled()
                        ->dehydrated(true),
                    //->hint('Sales Order'), // tampilkan hint jika placeholder tidak efektif, // Tampilkan label, bukan kode
                    // tetap dikirim ke backend saat submit form

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

                // Repeater
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
                    ->visible(
                        fn(Order $record) =>
                        Filament::auth()->user()?->hasRole('sales') &&
                            $record->category === 'SO' &&
                            $record->status === OrderStatus::Approved
                    )
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
                            ['sales_id' => $record->sales_id, 'order_id' => $record->id],
                            ['amount' => $record->total * 0.10]
                        );
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Order $record) {
                        $user = Filament::auth()->user();
                        $isPending = $record->status === OrderStatus::Pending;
                        $notSelfCreated = $record->created_by !== $user->id;

                        if ($user->hasRole('client')) {
                            return $isPending && $record->client_id === $user->client?->id && $notSelfCreated;
                        }
                        if ($user->hasRole('sales')) {
                            return $isPending && $record->sales_id === $user->employee?->sales?->id && $notSelfCreated;
                        }

                        return false;
                    })
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::Approved]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Approved,
                            'notes' => 'Order approved by receiver',
                        ]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (Order $record) {
                        $user = Filament::auth()->user();
                        $isPending = $record->status === OrderStatus::Pending;
                        $notSelfCreated = $record->created_by !== $user->id;

                        if ($user->hasRole('client')) {
                            return $isPending && $record->client_id === $user->client?->id && $notSelfCreated;
                        }
                        if ($user->hasRole('sales')) {
                            return $isPending && $record->sales_id === $user->employee?->sales?->id && $notSelfCreated;
                        }

                        return false;
                    })
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::Rejected]);

                        OrderFlow::create([
                            'order_id' => $record->id,
                            'user_id' => auth()->id(),
                            'from_status' => OrderStatus::Pending,
                            'to_status' => OrderStatus::Rejected,
                            'notes' => 'Order rejected by receiver',
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
