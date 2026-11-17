<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('customer.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);
        
        $user->update($request->only([
            'name', 'phone', 'address', 'city', 'state', 'country'
        ]));
        
        return back()->with('success', 'Profile updated successfully');
    }

    public function uploadId(Request $request)
    {
        $request->validate([
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);
        
        $user = auth()->user();
        
        if ($user->id_document) {
            Storage::disk('public')->delete($user->id_document);
        }
        
        $path = $request->file('id_document')->store('id-documents', 'public');
        
        $user->update([
            'id_document' => $path,
            'id_verified' => false
        ]);
        
        return back()->with('success', 'ID document uploaded. Pending verification.');
    }

    public function connectGoogle(Request $request)
    {
        $request->validate(['google_id' => 'required']);
        
        $user = auth()->user();
        $user->update(['google_id' => $request->google_id]);
        
        return back()->with('success', 'Google account connected');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        $user = auth()->user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect');
        }
        
        $user->update(['password' => Hash::make($request->password)]);
        
        return back()->with('success', 'Password updated successfully');
    }
}
