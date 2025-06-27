<?php

namespace App\Filament\Sales\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Sales\Resources\OrderResource;
use App\Models\OrderFlow;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::id();

        $data['sales_id'] = Auth::id();
        $data['status'] = OrderStatus::Pending;

        return $data;
    }

    protected function afterCreate(): void
    {
        $order = $this->record;

        OrderFlow::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'from_status' => null,
            'to_status' => OrderStatus::Pending,
            'notes' => 'Order created by Sales and sent to Client',
        ]);
    }
}
