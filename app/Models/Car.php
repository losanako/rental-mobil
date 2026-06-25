<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'plate_number',
        'year',
        'color',
        'price_per_day',
        'status'
    ];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}