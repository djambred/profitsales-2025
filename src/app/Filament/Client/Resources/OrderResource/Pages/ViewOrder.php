<?php

namespace App\Filament\Client\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Client\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;


    public function getInfolist(string $name): ?Infolist
    {
        return Infolist::make()
            ->schema([
                Section::make('Order Info')
                    ->schema([
                        TextEntry::make('order_number')->label('Order Number'),
                        TextEntry::make('status')
                            ->badge()
                            ->label('Status')
                            ->formatStateUsing(fn($state) => \App\Enums\OrderStatus::from($state)->label()),
                        TextEntry::make('total')->label('Total')->money('IDR'),
                        TextEntry::make('notes')->label('Notes'),
                    ])
                    ->columns(2),

                Section::make('Product Details')
                    ->schema([
                        RepeatableEntry::make('orderDetails')
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('product_name')->label('Product'),
                                    TextEntry::make('quantity')->label('Qty'),
                                    TextEntry::make('price')->label('Price')->money('IDR'),
                                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                                ])
                            ])
                    ])
                    ->collapsible(),

                Section::make('Order Flow History')
                    ->schema([
                        RepeatableEntry::make('orderFlows')
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('created_at')
                                        ->label('Date')
                                        ->dateTime('Y-m-d H:i'),
                                    TextEntry::make('sales.employee.user.name')
                                        ->label('By')
                                        ->default(fn($record) => $record->user?->name ?? '-'),
                                    TextEntry::make('from_status')
                                        ->label('From')
                                        ->default(fn($state) => $state ? \App\Enums\OrderStatus::from($state)->label() : '-'),
                                    TextEntry::make('to_status')
                                        ->label('To')
                                        ->formatStateUsing(fn($state) => \App\Enums\OrderStatus::from($state)->label()),
                                    TextEntry::make('notes')->label('Notes')->columnSpanFull(),
                                ])
                            ])
                            ->default(fn($record) => $record->orderFlows()->latest()->get())
                    ])
                    ->collapsible(),
            ]);
    }
}
