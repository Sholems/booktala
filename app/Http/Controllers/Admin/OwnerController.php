<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;

/**
 * Property owners: G-Briel's own portfolio and the landlords it manages for.
 */
class OwnerController extends Controller
{
    public function index()
    {
        $owners = Owner::withCount('properties')
            ->orderByDesc('is_company_owned')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.owners.index', compact('owners'));
    }

    public function create()
    {
        return view('admin.owners.form', ['owner' => new Owner(['type' => 'individual', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $owner = Owner::create($this->validated($request));

        return redirect()->route('admin.owners.edit', $owner)->with('success', 'Owner added');
    }

    public function edit(Owner $owner)
    {
        $owner->load(['properties' => fn ($q) => $q->with('site')->orderBy('title')]);

        return view('admin.owners.form', compact('owner'));
    }

    public function update(Request $request, Owner $owner)
    {
        $owner->update($this->validated($request, $owner));

        return redirect()->route('admin.owners.edit', $owner)->with('success', 'Owner updated');
    }

    public function destroy(Owner $owner)
    {
        if ($owner->is_company_owned) {
            return back()->with('error', 'The company owner cannot be deleted.');
        }
        if ($owner->properties()->exists()) {
            return back()->with('error', 'Move this owner\'s properties to another owner first, or mark the owner inactive.');
        }

        $owner->delete();

        return redirect()->route('admin.owners.index')->with('success', 'Owner deleted');
    }

    /**
     * Create or update the owner's portal login. Head office sets the password
     * and shares it with the owner securely.
     */
    public function portalAccess(Request $request, Owner $owner)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($owner->user_id)],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ]);

        $user = $owner->user;
        if ($user && !$user->hasRole('owner')) {
            return back()->with('error', 'This owner is linked to a non-owner account; unlink it first.');
        }

        if ($user) {
            $user->update(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active']);
        } else {
            $user = \App\Models\User::create([
                'name' => $owner->name,
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $owner->phone,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('owner');
            $owner->update(['user_id' => $user->id]);
        }

        return back()->with('success', 'Owner portal login saved. Share the password with ' . $owner->name . ' securely.');
    }

    public function revokePortal(Owner $owner)
    {
        $owner->user?->update(['status' => 'suspended']);

        return back()->with('success', 'Owner portal access suspended');
    }

    private function validated(Request $request, ?Owner $owner = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:company,individual',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            // Management agreement and payout account.
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'bank_account_number' => ['nullable', 'regex:/^\d{10}$/'],
        ], ['bank_account_number.regex' => 'Enter the 10-digit NUBAN account number.']);
        $data['is_active'] = $request->boolean('is_active');
        $data['deduct_expenses'] = $request->boolean('deduct_expenses');
        $data['commission_rate'] = $data['commission_rate'] ?? ($owner?->commission_rate ?? 20);

        // The company owner always stays active; properties default to it.
        if ($owner?->is_company_owned) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
