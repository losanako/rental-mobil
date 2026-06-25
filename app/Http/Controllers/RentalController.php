<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Car;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RentalController extends Controller
{
    // GET /api/rentals
    public function index()
    {
        $rentals = Rental::with(['customer', 'car', 'payment'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $rentals
        ]);
    }

    // ✅ GET /api/rentals/{id}
    public function show($id)
    {
        try {
            $rental = Rental::with(['customer', 'car', 'payment'])->find($id);
            
            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $rental
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // POST /api/rentals
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'car_id' => 'required|exists:cars,id',
                'rental_date' => 'required|date',
                'return_date' => 'required|date|after:rental_date',
            ]);
            
            $car = Car::find($validated['car_id']);
            $start = Carbon::parse($validated['rental_date']);
            $end = Carbon::parse($validated['return_date']);
            $days = (int) $start->diffInDays($end) + 1;
            $total_price = $days * $car->price_per_day;
            
            $rental = Rental::create([
                'customer_id' => $validated['customer_id'],
                'car_id' => $validated['car_id'],
                'rental_date' => $validated['rental_date'],
                'return_date' => $validated['return_date'],
                'total_price' => $total_price,
                'status' => 'ongoing'
            ]);
            
            $car->update(['status' => 'rented']);
            
            return response()->json([
                'success' => true,
                'message' => 'Rental berhasil dibuat',
                'data' => $rental
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ PUT /api/rentals/{id} - UPDATE/PERPANJANG RENTAL
    public function update(Request $request, $id)
    {
        try {
            $rental = Rental::find($id);
            
            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental tidak ditemukan'
                ], 404);
            }
            
            $validated = $request->validate([
                'customer_id' => 'sometimes|exists:customers,id',
                'car_id' => 'sometimes|exists:cars,id',
                'rental_date' => 'sometimes|date',
                'return_date' => 'sometimes|date|after:rental_date',
                'status' => 'sometimes|in:ongoing,completed,cancelled'
            ]);
            
            // Jika ganti mobil
            if (isset($validated['car_id']) && $validated['car_id'] != $rental->car_id) {
                $rental->car->update(['status' => 'available']);
                Car::find($validated['car_id'])->update(['status' => 'rented']);
            }
            
            // Hitung ulang total price jika tanggal berubah
            if (isset($validated['return_date']) || isset($validated['rental_date']) || isset($validated['car_id'])) {
                $car = Car::find($validated['car_id'] ?? $rental->car_id);
                $start = Carbon::parse($validated['rental_date'] ?? $rental->rental_date);
                $end = Carbon::parse($validated['return_date'] ?? $rental->return_date);
                $days = (int) $start->diffInDays($end) + 1;
                $validated['total_price'] = $days * $car->price_per_day;
            }
            
            $rental->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Rental berhasil diupdate',
                'data' => $rental->load(['customer', 'car', 'payment'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/rentals/customer/{id}
    public function getByCustomer($id)
    {
        try {
            $customer = Customer::find($id);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan'
                ], 404);
            }
            
            $rentals = Rental::with(['car', 'payment'])
                ->where('customer_id', $id)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $rentals
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /api/rentals/{id}/status
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:ongoing,completed,cancelled'
            ]);
            
            $rental = Rental::find($id);
            
            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental tidak ditemukan'
                ], 404);
            }
            
            $rental->update(['status' => $validated['status']]);
            
            if ($validated['status'] === 'completed' || $validated['status'] === 'cancelled') {
                $rental->car->update(['status' => 'available']);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Status rental berhasil diupdate',
                'data' => $rental
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /api/rentals/{id}
    public function destroy($id)
    {
        try {
            $rental = Rental::find($id);
            
            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental tidak ditemukan'
                ], 404);
            }
            
            $rental->car->update(['status' => 'available']);
            $rental->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Rental berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}