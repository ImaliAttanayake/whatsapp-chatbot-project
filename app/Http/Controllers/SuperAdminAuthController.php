<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SuperAdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('superadmin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            if ($user->role === 'superadmin') {
                return redirect()->route('superadmin.admins.index');
            } else {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Access denied. Super Admin privileges required.',
                ]);
            }
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }
}
