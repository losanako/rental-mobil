<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Customer::with('rentals')->get()
        ]);
    }
    
    public function show($id)
    {
        $customer = Customer::with('rentals')->find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'identity_number' => 'required|string|unique:customers'
        ]);
        
        $customer = Customer::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil ditambahkan',
            'data' => $customer
        ], 201);
    }
    
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'identity_number' => ['sometimes', 'string', 'unique:customers,identity_number,' . $id]
        ]);
        
        $customer->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil diupdate',
            'data' => $customer
        ]);
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
        
        $customer->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dihapus'
        ]);
    }
}