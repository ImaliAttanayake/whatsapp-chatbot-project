<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
        return view('superadmin.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('superadmin.admins.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,superadmin',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_admin' => true,
        ]);

        return redirect()->route('superadmin.admins.index')->with('success', 'Admin created successfully.');
    }

    public function show(User $admin)
    {
        return view('superadmin.admins.show', compact('admin'));
    }

    public function edit(User $admin)
    {
        return view('superadmin.admins.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($admin->id)],
            'role' => 'required|in:admin,superadmin',
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return redirect()->route('superadmin.admins.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(User $admin)
    {
        // Prevent deleting the current superadmin
        if ($admin->id === auth()->id()) {
            return redirect()->route('superadmin.admins.index')->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();

        return redirect()->route('superadmin.admins.index')->with('success', 'Admin deleted successfully.');
    }
}
