<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $fillable = [
        'customer_id',
        'car_id',
        'rental_date',
        'return_date',
        'total_price',
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}