<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderFlow;

class Order extends Model
{
    protected $fillable = [
        'client_id',
        'sales_id',
        'order_number',
        'category',
        'status',
        'total',
        'profit',
        'sales_profit',
        'notes',
    ];
    protected $casts = [
        'status' => OrderStatus::class,
    ];
    protected $attributes = [
        'status' => 'pending',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function calculateProfit(): float
    {
        return $this->orderDetails->sum(function ($detail) {
            $cost = $detail->product->cost_price ?? 0;
            return ($detail->price - $cost) * $detail->quantity;
        });
    }

    public function calculateSalesProfit(float $rate = 0.1): float
    {
        return round($this->calculateProfit() * $rate, 2);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            $nextId = Order::max('id') + 1;
            do {
                $orderNumber = 'INV-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                $exists = Order::where('order_number', $orderNumber)->exists();
                $nextId++;
            } while ($exists);

            $order->order_number = $orderNumber;
        });
        static::created(function ($order) {
            OrderFlow::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
                'from_status' => null,
                'to_status'   => $order->status?->value ?? 'pending',
                'notes'       => 'Order created by Sales.',
            ]);
        });
    }

    public function flows()
    {
        return $this->hasMany(OrderFlow::class);
    }
}
