<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'staff', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role, array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name' => ucfirst($role).' User',
            'email' => $role.uniqid().'@example.com',
            'password' => 'password123',
            'status' => 'active',
        ], $attrs));
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_open_staff_edit_page(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $this->actingAs($admin)->get(route('admin.staff.edit', $staff))
            ->assertOk()
            ->assertSee($staff->email)
            ->assertSee('Reset password');
    }

    public function test_admin_can_update_staff_details(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '08012345678',
            'status' => 'suspended',
        ])->assertRedirect(route('admin.staff.edit', $staff));

        $staff->refresh();
        $this->assertSame('Updated Name', $staff->name);
        $this->assertSame('updated@example.com', $staff->email);
        $this->assertSame('08012345678', $staff->phone);
        $this->assertSame('suspended', $staff->status);
        $this->assertFalse($staff->staff->is_active);
    }

    public function test_update_rejects_email_used_by_another_user(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $other = $this->makeUser('customer');

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $other->email,
            'status' => 'active',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_reset_staff_password_and_staff_can_log_in_with_it(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $oldToken = $staff->remember_token;

        $this->actingAs($admin)->put(route('admin.staff.password', $staff), [
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect(route('admin.staff.edit', $staff));

        $staff->refresh();
        $this->assertTrue(Hash::check('NewSecret123', $staff->password));
        $this->assertNotSame($oldToken, $staff->remember_token);
    }

    public function test_password_reset_requires_confirmation_and_minimum_length(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $this->actingAs($admin)->put(route('admin.staff.password', $staff), [
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password123', $staff->fresh()->password));
    }

    public function test_staff_routes_cannot_touch_admin_or_customer_accounts(): void
    {
        $admin = $this->makeUser('admin');
        $superAdmin = $this->makeUser('super_admin');
        $customer = $this->makeUser('customer');

        foreach ([$superAdmin, $customer] as $target) {
            $this->actingAs($admin)->put(route('admin.staff.password', $target), [
                'password' => 'NewSecret123',
                'password_confirmation' => 'NewSecret123',
            ])->assertNotFound();

            $this->actingAs($admin)->delete(route('admin.staff.destroy', $target))->assertNotFound();
            $this->assertNotNull($target->fresh());
        }
    }

    public function test_staff_cannot_manage_other_staff(): void
    {
        $staff = $this->makeUser('staff');
        $colleague = $this->makeUser('staff');

        $this->actingAs($staff)->put(route('admin.staff.password', $colleague), [
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertForbidden();
    }
}
