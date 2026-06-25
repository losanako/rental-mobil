<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Payment;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $rentals = Rental::with(['customer', 'car', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $payments = Payment::with('rental.customer', 'rental.car')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'rentals' => $rentals,
                'payments' => $payments
            ]
        ]);
    }
    
    public function rentalHistory()
    {
        $rentals = Rental::with(['customer', 'car', 'payment'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rentals
        ]);
    }
    
    public function paymentHistory()
    {
        $payments = Payment::with('rental.customer', 'rental.car')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
}