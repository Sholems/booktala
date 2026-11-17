<?php

namespace Tests;

use App\Models\Position;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views use @vite; tests should not depend on a built manifest.
        $this->withoutVite();
    }

    /**
     * For single-team scenarios: put every staff member on every site, in a
     * position matching their department. Multi-site rules are tested in
     * MultiPropertyAccessTest with explicit assignments instead.
     */
    protected function staffWorkEverywhere(): void
    {
        foreach (User::role('staff')->doesntHave('staff')->get() as $user) {
            Staff::create(['user_id' => $user->id, 'department' => 'frontdesk', 'employee_id' => 'T-' . $user->id, 'hire_date' => today()]);
        }

        foreach (Staff::with('activeAssignments')->get() as $staff) {
            $position = Position::where('department', $staff->department ?? 'frontdesk')->where('level', 'staff')->first()
                ?? Position::where('name', 'Receptionist')->firstOrFail();

            foreach (Site::all() as $site) {
                if (!$staff->activeAssignments->contains('site_id', $site->id)) {
                    $staff->assignTo($site, $position);
                }
            }
        }
    }
}
