<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SalesTargetResource\Pages;
use App\Filament\Admin\Resources\SalesTargetResource\RelationManagers;
use App\Models\Branch;
use App\Models\SalesTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Filters\SelectFilter;

class SalesTargetResource extends Resource
{
    protected static ?string $model = SalesTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('achievement')
                    ->label('Progress')
                    ->state(function ($record) {
                        $achieved = $record->achievedValue();
                        $target = $record->target_value ?: 1;
                        return round(($achieved / $target) * 100) . '%';
                    })
                    ->color(function ($record) {
                        return $record->isAchieved() ? 'success' : 'danger';
                    }),
                TextColumn::make('user.name')
                    ->label('Sales'),

                TextColumn::make('user.employee.branch.name')
                    ->label('Branch'),

                TextColumn::make('month')
                    ->label('Month')
                    ->date('F Y'),

                TextColumn::make('target_value')
                    ->label('Target Value')
                    ->numeric(),

                TextColumn::make('achieved_value')
                    ->label('Achieved')
                    ->state(fn($record) => $record->achievedValue()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => $record->isAchieved() ? 'success' : 'danger')
                    ->state(fn($record) => $record->isAchieved() ? 'Achieved' : 'Not Achieved'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('Generate Targets')
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->label('Branch')
                            ->required(),

                        Forms\Components\DatePicker::make('month')
                            ->label('Month')
                            ->required(),

                        Forms\Components\TextInput::make('target_value')
                            ->numeric()
                            ->required()
                            ->label('Target Value per Sales'),
                    ])
                    ->action(function (array $data) {
                        $month = \Carbon\Carbon::parse($data['month'])->startOfMonth();

                        $salesUsers = \App\Models\User::whereHas('employee', function ($query) use ($data) {
                            $query->where('branch_id', $data['branch_id']);
                        })
                            ->role('sales')
                            ->get();

                        foreach ($salesUsers as $sales) {
                            \App\Models\SalesTarget::updateOrCreate([
                                'user_id' => $sales->id,
                                'month' => $month,
                            ], [
                                'branch_id' => $data['branch_id'],
                                'target_value' => $data['target_value'],
                            ]);
                        }

                        Notification::make()
                            ->title('Targets generated successfully!')
                            ->success()
                            ->send();
                    })
            ])

            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('user.employee', function ($q) use ($data) {
                            $q->where('branch_id', $data['value']);
                        });
                    }),

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
            'index' => Pages\ListSalesTargets::route('/'),
            'create' => Pages\CreateSalesTarget::route('/create'),
            'edit' => Pages\EditSalesTarget::route('/{record}/edit'),
        ];
    }
}
