<?php

namespace App\Filament\Admin\Resources\SalesCommissionsResource\Pages;

use App\Filament\Admin\Resources\SalesCommissionsResource;
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
