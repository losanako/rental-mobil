<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'rental_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_status'
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}