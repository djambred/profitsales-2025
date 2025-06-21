<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommissions extends Model
{
    protected $fillable = [
        'sales_id',
        'order_id',
        'amount',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }
}
