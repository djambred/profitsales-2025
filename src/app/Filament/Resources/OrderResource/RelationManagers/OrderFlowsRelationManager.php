<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;

class OrderFlowsRelationManager extends RelationManager
{
    protected static string $relationship = 'flows'; // defined in Order model

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('from_status')
                    ->formatStateUsing(fn($state) => $state?->label() ?? '-')
                    ->label('From'),
                TextColumn::make('to_status')
                    ->formatStateUsing(fn($state) => $state->label())
                    ->label('To')
                    ->badge(),
                TextColumn::make('notes')->wrap(),
                TextColumn::make('user.name')->label('Changed By'),
                TextColumn::make('created_at')->since()->label('Time'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
