<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' User '.uniqid(),
            'email' => $role.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        if ($role === 'staff') {
            Staff::create([
                'user_id' => $user->id,
                'department' => 'frontdesk',
                'employee_id' => 'STF-'.$user->id,
                'hire_date' => today(),
            ]);
        }

        return $user;
    }

    public function test_admin_can_view_profile_page(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('admin.profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Account Profile');
        $response->assertSee($admin->email);
    }

    public function test_admin_can_update_bio_and_details(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'name' => 'Alexander SuperAdmin',
            'email' => 'alexander.admin@booktala.com',
            'phone' => '+2348011223344',
            'job_title' => 'Director of Hospitality Operations',
            'bio' => 'Experienced short-let and luxury hotel operations manager with over 10 years experience.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertEquals('Alexander SuperAdmin', $admin->name);
        $this->assertEquals('alexander.admin@booktala.com', $admin->email);
        $this->assertEquals('+2348011223344', $admin->phone);
        $this->assertEquals('Director of Hospitality Operations', $admin->job_title);
        $this->assertEquals('Experienced short-let and luxury hotel operations manager with over 10 years experience.', $admin->bio);
    }

    public function test_admin_can_upload_and_delete_profile_picture(): void
    {
        $admin = $this->makeUser('admin');
        $file = UploadedFile::fake()->image('admin-avatar.jpg', 400, 400);

        $uploadResponse = $this->actingAs($admin)->post(route('admin.profile.avatar'), [
            'avatar' => $file,
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        $admin->refresh();
        $this->assertNotNull($admin->avatar);
        $this->assertNotNull($admin->avatar_url);
        Storage::disk('public')->assertExists($admin->avatar);

        // Now remove photo
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.profile.avatar.destroy'));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $admin->refresh();
        $this->assertNull($admin->avatar);
    }

    public function test_admin_can_update_password(): void
    {
        $admin = $this->makeUser('admin');

        // Invalid current password
        $failResponse = $this->actingAs($admin)->post(route('admin.profile.password'), [
            'current_password' => 'wrongpassword',
            'password' => 'newsecretpassword123',
            'password_confirmation' => 'newsecretpassword123',
        ]);

        $failResponse->assertSessionHasErrors('current_password');

        // Valid current password
        $successResponse = $this->actingAs($admin)->post(route('admin.profile.password'), [
            'current_password' => 'password123',
            'password' => 'newsecretpassword123',
            'password_confirmation' => 'newsecretpassword123',
        ]);

        $successResponse->assertRedirect();
        $successResponse->assertSessionHas('success');

        $admin->refresh();
        $this->assertTrue(Hash::check('newsecretpassword123', $admin->password));
    }

    public function test_staff_can_view_profile_page(): void
    {
        $staff = $this->makeUser('staff');

        $response = $this->actingAs($staff)->get(route('staff.profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Staff Profile');
        $response->assertSee($staff->email);
    }

    public function test_staff_can_update_bio_details_and_emergency_contact(): void
    {
        $staffUser = $this->makeUser('staff');

        $response = $this->actingAs($staffUser)->patch(route('staff.profile.update'), [
            'name' => 'Fatima Ibrahim',
            'email' => 'fatima.ibrahim@booktala.com',
            'phone' => '+2348099887766',
            'job_title' => 'Lead Guest Concierge Specialist',
            'bio' => 'Passionate about VIP guest service and ensuring impeccable experiences across our properties.',
            'emergency_contact_name' => 'Musa Ibrahim',
            'emergency_contact_phone' => '+2348055443322',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staffUser->refresh();
        $this->assertEquals('Fatima Ibrahim', $staffUser->name);
        $this->assertEquals('fatima.ibrahim@booktala.com', $staffUser->email);
        $this->assertEquals('+2348099887766', $staffUser->phone);
        $this->assertEquals('Lead Guest Concierge Specialist', $staffUser->job_title);
        $this->assertEquals('Passionate about VIP guest service and ensuring impeccable experiences across our properties.', $staffUser->bio);

        $staffProfile = $staffUser->staff->fresh();
        $this->assertEquals('Musa Ibrahim', $staffProfile->emergency_contact_name);
        $this->assertEquals('+2348055443322', $staffProfile->emergency_contact_phone);
    }

    public function test_staff_can_upload_and_delete_profile_picture(): void
    {
        $staffUser = $this->makeUser('staff');
        $file = UploadedFile::fake()->image('staff-avatar.png', 300, 300);

        $uploadResponse = $this->actingAs($staffUser)->post(route('staff.profile.avatar'), [
            'avatar' => $file,
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        $staffUser->refresh();
        $this->assertNotNull($staffUser->avatar);
        $this->assertNotNull($staffUser->avatar_url);
        Storage::disk('public')->assertExists($staffUser->avatar);

        // Delete photo
        $deleteResponse = $this->actingAs($staffUser)->delete(route('staff.profile.avatar.destroy'));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $staffUser->refresh();
        $this->assertNull($staffUser->avatar);
    }

    public function test_unauthorized_guests_cannot_access_admin_or_staff_profile(): void
    {
        $guest = $this->makeUser('customer');

        $this->actingAs($guest)->get(route('admin.profile.edit'))->assertStatus(403);
        $this->actingAs($guest)->get(route('staff.profile.edit'))->assertStatus(403);
    }
}
