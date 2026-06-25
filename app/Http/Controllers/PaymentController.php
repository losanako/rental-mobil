<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // GET /api/payments - Get All Payments
    public function index()
    {
        $payments = Payment::with(['rental.customer', 'rental.car'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
    
    // GET /api/payments/{id} - Get Payment by ID
    public function show($id)
    {
        $payment = Payment::with(['rental.customer', 'rental.car'])->find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    // POST /api/payments - Create Payment
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'rental_id' => 'required|exists:rentals,id',
                'payment_date' => 'required|date',
                'amount' => 'required|integer|min:0',
                'payment_method' => 'required|string|in:cash,card,transfer',
                'payment_status' => 'required|string|in:pending,paid,failed'
            ]);
            
            // Cek apakah sudah ada payment untuk rental ini
            $existingPayment = Payment::where('rental_id', $validated['rental_id'])->first();
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental ini sudah memiliki pembayaran'
                ], 422);
            }
            
            $payment = Payment::create($validated);
            
            // Jika payment status = paid, update status rental menjadi completed
            if ($validated['payment_status'] === 'paid') {
                $rental = Rental::find($validated['rental_id']);
                if ($rental && $rental->status !== 'completed') {
                    $rental->update(['status' => 'completed']);
                    // Update status mobil menjadi available
                    $rental->car->update(['status' => 'available']);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment berhasil dibuat',
                'data' => $payment->load(['rental.customer', 'rental.car'])
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // PUT /api/payments/{id} - Update Payment
    public function update(Request $request, $id)
    {
        try {
            $payment = Payment::find($id);
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment tidak ditemukan'
                ], 404);
            }
            
            $validated = $request->validate([
                'payment_date' => 'sometimes|date',
                'amount' => 'sometimes|integer|min:0',
                'payment_method' => 'sometimes|string|in:cash,card,transfer',
                'payment_status' => 'sometimes|string|in:pending,paid,failed'
            ]);
            
            // Jika payment_status berubah menjadi paid
            if (isset($validated['payment_status']) && $validated['payment_status'] === 'paid' && $payment->payment_status !== 'paid') {
                $rental = Rental::find($payment->rental_id);
                if ($rental && $rental->status !== 'completed') {
                    $rental->update(['status' => 'completed']);
                    $rental->car->update(['status' => 'available']);
                }
            }
            
            // Jika payment_status berubah dari paid menjadi pending/failed
            if (isset($validated['payment_status']) && $validated['payment_status'] !== 'paid' && $payment->payment_status === 'paid') {
                $rental = Rental::find($payment->rental_id);
                if ($rental && $rental->status === 'completed') {
                    $rental->update(['status' => 'ongoing']);
                    $rental->car->update(['status' => 'rented']);
                }
            }
            
            $payment->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment berhasil diupdate',
                'data' => $payment->load(['rental.customer', 'rental.car'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/payments/paid - Get Paid Payments
    public function paid()
    {
        $payments = Payment::where('payment_status', 'paid')
            ->with(['rental.customer', 'rental.car'])
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
    
    // DELETE /api/payments/{id} - Delete Payment
    public function destroy($id)
    {
        try {
            $payment = Payment::find($id);
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment tidak ditemukan'
                ], 404);
            }
            
            $payment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}