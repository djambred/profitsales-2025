<?php

namespace App\Filament\Sales\Resources\SalesCommissionsResource\Pages;

use App\Filament\Sales\Resources\SalesCommissionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesCommissions extends EditRecord
{
    protected static string $resource = SalesCommissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
