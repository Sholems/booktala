<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProfileConnectGoogleRequest;
use App\Http\Requests\Customer\ProfileUpdatePasswordRequest;
use App\Http\Requests\Customer\ProfileUpdateRequest;
use App\Http\Requests\Customer\ProfileUploadIdRequest;
use App\Support\IdDocuments;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return view('customer.profile.edit', compact('user'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();

        $request->validated();

        $user->update($request->only([
            'name', 'phone', 'address', 'city', 'state', 'country',
        ]));

        return back()->with('success', 'Profile updated successfully');
    }

    public function uploadId(ProfileUploadIdRequest $request)
    {
        $request->validated();

        $user = auth()->user();

        // ID documents are personal data: private storage only, never a public URL.
        IdDocuments::delete($user->id_document);
        $path = IdDocuments::store($request->file('id_document'));

        $user->update([
            'id_document' => $path,
            'id_verified' => false,
        ]);

        return back()->with('success', 'ID document uploaded. Pending verification.');
    }

    public function connectGoogle(ProfileConnectGoogleRequest $request)
    {
        $request->validated();

        $user = auth()->user();
        $user->update(['google_id' => $request->google_id]);

        return back()->with('success', 'Google account connected');
    }

    public function updatePassword(ProfileUpdatePasswordRequest $request)
    {
        $request->validated();

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password updated successfully');
    }
}
