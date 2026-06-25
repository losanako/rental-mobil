<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.']
            ]);
        }
        
        $token = $user->createToken('auth-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token
        ]);
    }
    
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        
        $token = $user->createToken('auth-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
    
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }
}