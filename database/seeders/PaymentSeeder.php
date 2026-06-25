<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            [
                'rental_id' => 1,
                'payment_date' => '2024-01-01',
                'amount' => 500000,
                'payment_method' => 'cash',
                'payment_status' => 'paid'
            ],
            [
                'rental_id' => 2,
                'payment_date' => '2024-01-05',
                'amount' => 1100000,
                'payment_method' => 'transfer',
                'payment_status' => 'pending'
            ],
        ];
        
        foreach ($payments as $payment) {
            Payment::create($payment);
        }
    }
}