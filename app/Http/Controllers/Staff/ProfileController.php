<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ProfileUpdateAvatarRequest;
use App\Http\Requests\Staff\ProfileUpdatePasswordRequest;
use App\Http\Requests\Staff\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load(['staff.assignments.site', 'staff.assignments.position']);
        $staff = $user->staff;

        return view('staff.profile.edit', compact('user', 'staff'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        if ($user->staff) {
            $user->staff->update([
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            ]);
        }

        return back()->with('success', 'Staff profile updated successfully.');
    }

    public function updateAvatar(ProfileUpdateAvatarRequest $request): RedirectResponse
    {
        $request->validated();

        $user = auth()->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function destroyAvatar(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return back()->with('success', 'Profile picture removed.');
    }

    public function updatePassword(ProfileUpdatePasswordRequest $request): RedirectResponse
    {
        $request->validated();

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
