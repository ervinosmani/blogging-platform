<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    //Regjistrim i ri
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    // Login dhe krijim token-i
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    //Logout 
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    //Merr te dhenat e perdoruesit aktual
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    //Perditeso profilin
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if($request->name) $user->name = $request->name;
        if($request->email) $user->email = $request->email;
        if($request->password) $user->password = bcrypt($request->password);

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }
}
