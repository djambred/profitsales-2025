<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $tables = 'sales_targets';
    protected $guarded = ['id'];

    public function achievedValue(): int
    {
        return \App\Models\Order::whereHas('sales', function ($query) {
            $query->where('user_id', $this->user_id);
        })
            ->where('category', 'PO')
            ->whereMonth('created_at', \Carbon\Carbon::parse($this->month)->month)
            ->whereYear('created_at', \Carbon\Carbon::parse($this->month)->year)
            ->count(); // 👈 gunakan count
    }

    public function isAchieved(): bool
    {
        return $this->achievedValue() >= $this->target_value;
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
