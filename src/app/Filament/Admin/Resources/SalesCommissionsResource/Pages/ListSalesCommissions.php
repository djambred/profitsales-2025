<?php

namespace App\Filament\Admin\Resources\SalesCommissionsResource\Pages;

use App\Filament\Admin\Resources\SalesCommissionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesCommissions extends ListRecords
{
    protected static string $resource = SalesCommissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
