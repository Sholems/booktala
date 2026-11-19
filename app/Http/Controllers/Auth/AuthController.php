<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthLoginRequest;
use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(AuthLoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return back()->withErrors(['email' => 'User not found']);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid password']);
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account is not active. Please contact support.']);
        }

        if (! $user->hasRole(['super_admin', 'admin', 'staff', 'owner', 'customer'])) {
            return back()->withErrors(['email' => 'No role assigned to this user. Please contact support.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('staff')) {
            return redirect()->route('staff.dashboard');
        } elseif ($user->hasRole('owner')) {
            return redirect()->route('owner.dashboard');
        } else {
            return redirect()->route('customer.dashboard');
        }
    }

    public function register(AuthRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        $user->assignRole('customer');

        Auth::login($user);

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
