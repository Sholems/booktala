<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffAddAssignmentRequest;
use App\Http\Requests\Admin\StaffResetPasswordRequest;
use App\Http\Requests\Admin\StaffStoreRequest;
use App\Http\Requests\Admin\StaffUpdateRequest;
use App\Models\Position;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::role('staff')
            ->with(['staff.primaryAssignment.position', 'staff.activeAssignments.site'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.staff.index', [
            'staff' => $staff,
            'positions' => Position::orderBy('department')->orderBy('name')->get(),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StaffStoreRequest $request)
    {
        $request->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'status' => 'active',
        ]);
        $user->assignRole('staff');

        // The staff profile is what shifts are scheduled against.
        $profile = Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'WP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'hire_date' => today(),
            'is_active' => true,
        ]);

        // Their first post: what they do and where. This decides what they can see.
        $profile->assignTo(Site::findOrFail($request->site_id), Position::findOrFail($request->position_id), true);

        return redirect()->route('admin.staff.index')->with('success', 'Staff created');
    }

    public function edit(User $user)
    {
        $this->ensureStaff($user);
        $user->load(['staff.assignments' => fn ($q) => $q->with(['site', 'position'])->orderByRaw('ends_on IS NOT NULL')->orderByDesc('is_primary')->orderByDesc('starts_on')]);

        return view('admin.staff.edit', [
            'member' => $user,
            'positions' => Position::orderBy('department')->orderBy('name')->get(),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(StaffUpdateRequest $request, User $user)
    {
        $this->ensureStaff($user);

        $data = $request->validated();

        $user->update($data);
        Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'is_active' => $data['status'] === 'active',
                'employee_id' => $user->staff->employee_id ?? 'WP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'hire_date' => $user->staff->hire_date ?? today(),
            ]
        );

        if ($data['status'] !== 'active') {
            $this->signOutEverywhere($user);
        }

        return redirect()->route('admin.staff.edit', $user)->with('success', 'Staff details updated');
    }

    public function resetPassword(StaffResetPasswordRequest $request, User $user)
    {
        $this->ensureStaff($user);

        $request->validated();

        // The model's "hashed" cast hashes the new password on save.
        $user->password = $request->password;
        $user->save();
        $this->signOutEverywhere($user);

        return redirect()->route('admin.staff.edit', $user)
            ->with('success', 'Password reset. Share the new password with '.$user->name.' securely.');
    }

    public function destroy(User $user)
    {
        $this->ensureStaff($user);
        $user->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff deleted');
    }

    /**
     * Post a staff member to a site in a position (they may hold several, e.g. a
     * technician covering three buildings).
     */
    public function addAssignment(StaffAddAssignmentRequest $request, User $user)
    {
        $this->ensureStaff($user);

        $data = $request->validated();

        $profile = $this->profile($user);
        $alreadyThere = $profile->activeAssignments()
            ->where('site_id', $data['site_id'])
            ->where('position_id', $data['position_id'])
            ->exists();
        if ($alreadyThere) {
            return back()->with('error', $user->name.' already holds that position at that site.');
        }

        $assignment = $profile->assignTo(
            Site::findOrFail($data['site_id']),
            Position::findOrFail($data['position_id']),
            $request->boolean('is_primary'),
            $data['starts_on'] ?? null,
        );

        return back()->with('success', $user->name.' assigned as '.$assignment->position->name.' at '.$assignment->site->name);
    }

    /**
     * End an assignment from today. It stays in the history; access to that site stops.
     */
    public function endAssignment(User $user, StaffAssignment $assignment)
    {
        $this->ensureStaff($user);
        abort_unless($assignment->staff_id === $user->staff?->id, 404);

        if ($assignment->ends_on !== null && $assignment->ends_on->lt(today())) {
            return back()->with('error', 'This assignment has already ended.');
        }

        // An assignment starting today or later is simply withdrawn.
        if ($assignment->starts_on->gte(today())) {
            $assignment->delete();
        } else {
            $assignment->update(['ends_on' => today()->subDay(), 'is_primary' => false]);
        }

        // Promote another current post to primary so the department stays meaningful.
        $profile = $user->staff;
        if (! $profile->activeAssignments()->where('is_primary', true)->exists()
            && ($next = $profile->activeAssignments()->with('position')->first())) {
            $next->update(['is_primary' => true]);
            $profile->update(['department' => $next->position->department]);
        }

        return back()->with('success', 'Assignment ended. '.$user->name.' no longer sees '.$assignment->site->name.'.');
    }

    public function makePrimary(User $user, StaffAssignment $assignment)
    {
        $this->ensureStaff($user);
        abort_unless($assignment->staff_id === $user->staff?->id && $assignment->isActive(), 404);

        $user->staff->assignments()->update(['is_primary' => false]);
        $assignment->update(['is_primary' => true]);
        $user->staff->update(['department' => $assignment->position->department]);

        return back()->with('success', 'Primary post updated');
    }

    private function profile(User $user): Staff
    {
        return $user->staff ?? Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'WP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'hire_date' => today(),
            'is_active' => $user->status === 'active',
        ]);
    }

    /**
     * These routes manage staff only; never let them touch admin or customer accounts.
     */
    private function ensureStaff(User $user): void
    {
        abort_unless($user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin']), 404);
    }

    /**
     * End the user's existing logins so a reset password or suspension takes effect at once.
     */
    private function signOutEverywhere(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
