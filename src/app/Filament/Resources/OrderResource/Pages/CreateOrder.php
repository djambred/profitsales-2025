<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        do {
            $orderNumber = 'INV-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $orderNumber)->exists());

        $data['order_number'] = $orderNumber;

        return $data;
    }
}
