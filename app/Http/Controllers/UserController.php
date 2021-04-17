<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class UserController extends BaseController
{
    public function login(Request $request)
    {
        $login = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($login)) {
            return response(['message' => 'Invalid login']);
        }

        $accessToken = Auth::user()->createToken('autoToken')->accessToken;

        return \response([
            'user' => Auth::user(),
            'accessToken' => $accessToken
        ]);
    }

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);

        User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
        ]);

        return $this->login($request);
    }

    public function test(Request $request)
    {
        return \response([
            'user' => $request->user()
                ->debitCards()
                ->active()
                ->get()
        ]);
    }
}
