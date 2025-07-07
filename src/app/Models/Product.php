<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $tables = 'products';

    protected $guarded = ['id'];
}
