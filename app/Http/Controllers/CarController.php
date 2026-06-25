<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Car::all()
        ]);
    }

    // PERBAIKAN: Dengan validasi ID numeric
    public function show($id)
    {
        // Validasi: ID harus numeric
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID mobil harus berupa angka'
            ], 400);
        }
        
        $car = Car::find((int) $id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Mobil tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $car
        ]);
    }

    public function store(Request $request)
    {
        if (is_array($request->all()) && !isset($request->brand)) {
            $successCount = 0;
            $errors = [];
            
            foreach ($request->all() as $index => $carData) {
                try {
                    $validated = validator($carData, [
                        'brand' => 'required|string|max:255',
                        'model' => 'required|string|max:255',
                        'plate_number' => 'required|string|unique:cars',
                        'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                        'color' => 'required|string|max:50',
                        'price_per_day' => 'required|integer|min:0',
                        'status' => 'required|in:available,rented,maintenance'
                    ])->validate();
                    
                    Car::create($validated);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Data ke-" . ($index + 1) . " gagal: " . $e->getMessage();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil menambah {$successCount} mobil",
                'total_data' => count($request->all()),
                'success_count' => $successCount,
                'failed_count' => count($request->all()) - $successCount,
                'errors' => $errors
            ]);
        }
        
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'plate_number' => 'required|string|unique:cars',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:50',
            'price_per_day' => 'required|integer|min:0',
            'status' => 'required|in:available,rented,maintenance'
        ]);
        
        $car = Car::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Mobil berhasil ditambahkan',
            'data' => $car
        ], 201);
    }

    // PERBAIKAN: Dengan validasi ID numeric
    public function update(Request $request, $id)
    {
        // Validasi: ID harus numeric
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID mobil harus berupa angka'
            ], 400);
        }
        
        $car = Car::find((int) $id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Mobil tidak ditemukan'
            ], 404);
        }
        
        $validated = $request->validate([
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'plate_number' => ['sometimes', 'string', Rule::unique('cars')->ignore($id)],
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|string|max:50',
            'price_per_day' => 'sometimes|integer|min:0',
            'status' => ['sometimes', Rule::in(['available', 'rented', 'maintenance'])]
        ]);
        
        $car->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Mobil berhasil diupdate',
            'data' => $car
        ]);
    }

    // PERBAIKAN: Dengan validasi ID numeric
    public function destroy($id)
    {
        // Validasi: ID harus numeric
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID mobil harus berupa angka'
            ], 400);
        }
        
        $car = Car::find((int) $id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Mobil tidak ditemukan'
            ], 404);
        }
        
        $car->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Mobil berhasil dihapus'
        ]);
    }
    
    public function batchStore(Request $request)
    {
        $cars = $request->all();
        $successCount = 0;
        $failed = [];
        
        foreach ($cars as $index => $carData) {
            try {
                $validated = validator($carData, [
                    'brand' => 'required|string|max:255',
                    'model' => 'required|string|max:255',
                    'plate_number' => 'required|string|unique:cars',
                    'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                    'color' => 'required|string|max:50',
                    'price_per_day' => 'required|integer|min:0',
                    'status' => 'required|in:available,rented,maintenance'
                ])->validate();
                
                Car::create($validated);
                $successCount++;
            } catch (\Exception $e) {
                $failed[] = [
                    'data' => $carData,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'total' => count($cars),
            'success_count' => $successCount,
            'failed_count' => count($failed),
            'failed_items' => $failed
        ]);
    }

    public function available()
    {
        $cars = Car::where('status', 'available')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Menampilkan mobil yang tersedia',
            'total' => $cars->count(),
            'data' => $cars
        ]);
    }
}