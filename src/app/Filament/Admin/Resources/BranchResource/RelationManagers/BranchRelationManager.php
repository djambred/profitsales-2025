<?php

namespace App\Filament\Admin\Resources\CompanyResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class BranchRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Branches';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('address')
                ->maxLength(255),

            Forms\Components\TextInput::make('state')
                ->maxLength(255),

            Forms\Components\TextInput::make('country')
                ->maxLength(255),

            Forms\Components\TextInput::make('postcode')
                ->maxLength(20),

            Forms\Components\TextInput::make('phone')
                ->maxLength(50),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->limit(30),

                Tables\Columns\TextColumn::make('phone'),
            ])
            ->filters([
                // Add filters here if needed
            ]);
    }
}
