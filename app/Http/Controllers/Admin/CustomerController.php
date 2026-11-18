<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::role('customer')->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user)
    {
        return view('admin.customers.show', compact('user'));
    }

    public function verify(User $user)
    {
        $user->update(['id_verified' => true]);

        return back()->with('success', 'Customer verified');
    }

    public function updateStatus(Request $request, User $user)
    {
        $user->update(['status' => $request->status]);

        return back()->with('success', 'Status updated');
    }
}
