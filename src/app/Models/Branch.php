<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'state',
        'country',
        'postcode',
        'phone',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(
            Order::class,
            Employee::class,
            'branch_id',     // FK di employees table
            'sales_id',      // FK di orders table
            'id',            // PK di branches table
            'id'             // PK di employees table
        )
            ->join('sales', 'sales.id', '=', 'orders.sales_id')
            ->whereColumn('sales.employee_id', 'employees.id');
    }
}
