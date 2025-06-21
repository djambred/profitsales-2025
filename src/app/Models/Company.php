<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'logo',
        'name',
        'address',
        'state',
        'country',
        'postcode',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
