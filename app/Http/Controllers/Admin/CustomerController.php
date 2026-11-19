<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IdDocuments;
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
        $this->ensureCustomer($user);
        $user->loadCount('reservations');

        return view('admin.customers.show', compact('user'));
    }

    /**
     * The guest's uploaded ID, streamed from private storage to staff who may verify it.
     */
    public function idDocument(User $user)
    {
        $this->ensureCustomer($user);

        return IdDocuments::response($user->id_document);
    }

    public function verify(User $user)
    {
        $this->ensureCustomer($user);

        if (! $user->id_document) {
            return back()->with('error', 'This guest has not uploaded an ID document.');
        }

        $user->update(['id_verified' => true]);

        return back()->with('success', 'Customer verified');
    }

    public function updateStatus(Request $request, User $user)
    {
        $this->ensureCustomer($user);
        $data = $request->validate(['status' => 'required|in:active,suspended']);

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Status updated');
    }

    /**
     * These routes manage guest accounts only, never staff or admin accounts.
     */
    private function ensureCustomer(User $user): void
    {
        abort_unless($user->hasRole('customer'), 404);
    }
}
