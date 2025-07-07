<?php

namespace App\Filament\Admin\Resources\SalesTargetResource\Pages;

use App\Filament\Admin\Resources\SalesTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesTarget extends EditRecord
{
    protected static string $resource = SalesTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
