<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        //REGISTRATION pag receive sa info
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);
        //himoag slot sa database
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])

        ]);
        //Token
        $token = $user->createToken('riderbuddytoken')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token], 201);
    }
    public function login(Request $request)
    {

        //Login
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string|'
        ]);

        //check Email
        $user = User::where('email', $fields['email'])->first();

        //Check password

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 401);


        }
        $token = $user->createToken('riderbuddytoken')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }
}

