<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $fillable = [
        'estimation_id',
        'name',
        'consumption_rate',
        'unit',
        'formula',
    ];
}