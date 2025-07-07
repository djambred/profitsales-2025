<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

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
        'created_by',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Relationships

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function flows(): HasMany
    {
        return $this->hasMany(OrderFlow::class);
    }

    // Profit Calculations

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

    // Boot logic

    protected static function booted()
    {
        static::creating(function ($order) {
            $user = Auth::user();

            // Generate unique invoice number
            $nextId = Order::max('id') + 1;
            do {
                $orderNumber = 'INV-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                $exists = Order::where('order_number', $orderNumber)->exists();
                $nextId++;
            } while ($exists);

            $order->order_number = $orderNumber;

            // Auto-fill created_by
            $order->created_by = $user?->id;

            // Set client_id if user is a client
            if ($user?->hasRole('client') && empty($order->client_id)) {
                $order->client_id = $user->client?->id;
            }

            // Set sales_id if user is a sales
            if ($user?->hasRole('sales') && empty($order->sales_id)) {
                $order->sales_id = $user->employee?->sales?->id;
            }
        });

        static::created(function ($order) {
            \App\Models\OrderFlow::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
                'from_status' => null,
                'to_status'   => $order->status?->value ?? 'pending',
                'notes'       => 'Order created by ' . (Auth::user()?->hasRole('client') ? 'Client' : 'Sales') . '.',
            ]);
        });
    }
}
