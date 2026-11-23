<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Position;
use App\Models\Property;
use App\Models\Site;
use App\Models\SiteMessage;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InternalCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'staff', 'customer', 'owner'] as $role) {
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

    private function makeSite(string $name = 'Victoria Island Court'): Site
    {
        return Site::create([
            'name' => $name,
            'address' => '12 Adeola Odeku, VI, Lagos',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'is_active' => true,
        ]);
    }

    private function makeStaff(User $user, Site $site, bool $isSupervisor = false): Staff
    {
        $staff = Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'STF'.rand(1000, 9999),
            'department' => 'Operations',
            'hire_date' => today(),
            'is_active' => true,
        ]);

        $position = Position::firstOrCreate([
            'name' => $isSupervisor ? 'Site Supervisor' : 'Receptionist',
        ], [
            'department' => $isSupervisor ? 'management' : 'frontdesk',
            'level' => $isSupervisor ? 'supervisor' : 'staff',
        ]);

        $staff->assignTo($site, $position, true);

        return $staff;
    }

    private function makeProperty(array $attrs = []): Property
    {
        return Property::create(array_merge([
            'title' => 'Penthouse 402 - The Opal',
            'slug' => 'penthouse-'.uniqid(),
            'description' => 'Luxury serviced penthouse.',
            'address' => '25 Banana Island Road',
            'city' => 'Ikoyi',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 3,
            'price_per_night' => 150000,
            'status' => 'active',
        ], $attrs));
    }

    public function test_staff_can_view_and_post_to_assigned_site_feed(): void
    {
        $site = $this->makeSite();
        $user = $this->makeUser('staff');
        $this->makeStaff($user, $site);

        $response = $this->actingAs($user)->get(route('staff.team.feed.index', ['site_id' => $site->id]));
        $response->assertOk();
        $response->assertSee('Site Operations Feed');

        $postResponse = $this->actingAs($user)->post(route('staff.team.feed.store'), [
            'site_id' => $site->id,
            'category' => 'general',
            'message' => 'Lobby reception desk cleaned and inventory verified.',
        ]);

        $postResponse->assertRedirect();
        $this->assertDatabaseHas('site_messages', [
            'site_id' => $site->id,
            'user_id' => $user->id,
            'category' => 'general',
            'message' => 'Lobby reception desk cleaned and inventory verified.',
        ]);
    }

    public function test_staff_cannot_post_to_unassigned_site_feed(): void
    {
        $siteA = $this->makeSite('Site A - Ikoyi');
        $siteB = $this->makeSite('Site B - Lekki');

        $user = $this->makeUser('staff');
        $this->makeStaff($user, $siteA);

        $response = $this->actingAs($user)->post(route('staff.team.feed.store'), [
            'site_id' => $siteB->id,
            'category' => 'urgent',
            'message' => 'Unauthorized broadcast attempt to unassigned site.',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('site_messages', [
            'site_id' => $siteB->id,
            'message' => 'Unauthorized broadcast attempt to unassigned site.',
        ]);
    }

    public function test_supervisor_can_pin_and_unpin_message_on_feed(): void
    {
        $site = $this->makeSite();
        $supervisorUser = $this->makeUser('staff');
        $this->makeStaff($supervisorUser, $site, isSupervisor: true);

        $regularUser = $this->makeUser('staff');
        $this->makeStaff($regularUser, $site, isSupervisor: false);

        $message = SiteMessage::create([
            'site_id' => $site->id,
            'user_id' => $regularUser->id,
            'category' => 'maintenance',
            'message' => 'Water pressure low on 3rd floor.',
            'pinned' => false,
        ]);

        // Regular staff cannot pin
        $this->actingAs($regularUser)
            ->patch(route('staff.team.feed.pin', $message))
            ->assertStatus(403);

        // Supervisor can pin
        $this->actingAs($supervisorUser)
            ->patch(route('staff.team.feed.pin', $message))
            ->assertRedirect();

        $this->assertTrue($message->fresh()->pinned);
        $this->assertEquals($supervisorUser->id, $message->fresh()->pinned_by);

        // Supervisor can unpin
        $this->actingAs($supervisorUser)
            ->patch(route('staff.team.feed.pin', $message))
            ->assertRedirect();

        $this->assertFalse($message->fresh()->pinned);
    }

    public function test_incoming_staff_can_acknowledge_shift_handover(): void
    {
        $site = $this->makeSite();

        $outgoingUser = $this->makeUser('staff');
        $outgoingStaff = $this->makeStaff($outgoingUser, $site);

        $incomingUser = $this->makeUser('staff');
        $this->makeStaff($incomingUser, $site);

        $attendance = Attendance::create([
            'staff_id' => $outgoingStaff->id,
            'site_id' => $site->id,
            'clock_in_at' => now()->subHours(9),
            'clock_out_at' => now()->subMinutes(15),
            'handover_note' => 'Guest in Flat 204 requested late checkout at 1 PM. Keys handed over.',
        ]);

        $this->assertFalse($attendance->isHandoverAcknowledged());

        // Incoming staff acknowledges handover
        $response = $this->actingAs($incomingUser)
            ->patch(route('staff.attendance.acknowledge-handover', $attendance));

        $response->assertRedirect();

        $attendance->refresh();
        $this->assertTrue($attendance->isHandoverAcknowledged());
        $this->assertEquals($incomingUser->id, $attendance->handover_acknowledged_by);
        $this->assertNotNull($attendance->handover_acknowledged_at);
    }

    public function test_staff_cannot_acknowledge_handover_at_unassigned_site(): void
    {
        $siteA = $this->makeSite('Site A');
        $siteB = $this->makeSite('Site B');

        $staffAUser = $this->makeUser('staff');
        $staffA = $this->makeStaff($staffAUser, $siteA);

        $staffBUser = $this->makeUser('staff');
        $this->makeStaff($staffBUser, $siteB);

        $attendance = Attendance::create([
            'staff_id' => $staffA->id,
            'site_id' => $siteA->id,
            'clock_in_at' => now()->subHours(8),
            'clock_out_at' => now(),
            'handover_note' => 'Shift handover notes for Site A.',
        ]);

        // Staff B attempts to acknowledge Site A's handover
        $response = $this->actingAs($staffBUser)
            ->patch(route('staff.attendance.acknowledge-handover', $attendance));

        $response->assertStatus(403);
    }

    public function test_owner_can_start_inquiry_and_reply_in_concierge(): void
    {
        $ownerUser = $this->makeUser('owner');
        $owner = Owner::create([
            'user_id' => $ownerUser->id,
            'name' => 'Chief Oladele Adeyemi',
            'email' => $ownerUser->email,
            'is_active' => true,
        ]);

        $property = $this->makeProperty([
            'owner_id' => $owner->id,
            'title' => 'Penthouse 402 - The Opal',
        ]);

        // Owner views concierge
        $this->actingAs($ownerUser)
            ->get(route('owner.messages.index'))
            ->assertOk()
            ->assertSee('Asset Concierge');

        // Owner creates inquiry
        $createResponse = $this->actingAs($ownerUser)->post(route('owner.messages.store'), [
            'property_id' => $property->id,
            'message' => 'Please provide the maintenance report for the AC repair on Oct 12.',
        ]);

        $conversation = Conversation::where('user_id', $ownerUser->id)->where('type', 'owner')->first();
        $this->assertNotNull($conversation);
        $this->assertEquals($property->id, $conversation->property_id);

        $createResponse->assertRedirect(route('owner.messages.show', $conversation));

        // Owner can reply
        $this->actingAs($ownerUser)->post(route('owner.messages.reply', $conversation), [
            'message' => 'Also check if the October statement has been posted.',
        ])->assertRedirect();

        $this->assertEquals(2, $conversation->messages()->count());
    }

    public function test_owner_cannot_create_inquiry_for_another_owners_property(): void
    {
        $ownerUser1 = $this->makeUser('owner');
        $owner1 = Owner::create([
            'user_id' => $ownerUser1->id,
            'name' => 'Owner One',
            'email' => $ownerUser1->email,
            'is_active' => true,
        ]);

        $ownerUser2 = $this->makeUser('owner');
        $owner2 = Owner::create([
            'user_id' => $ownerUser2->id,
            'name' => 'Owner Two',
            'email' => $ownerUser2->email,
            'is_active' => true,
        ]);

        $propertyOfOwner2 = $this->makeProperty([
            'owner_id' => $owner2->id,
            'title' => 'Villa Bella',
        ]);

        // Owner 1 attempts to create inquiry on Owner 2's property
        $response = $this->actingAs($ownerUser1)->post(route('owner.messages.store'), [
            'property_id' => $propertyOfOwner2->id,
            'message' => 'Snooping inquiry attempt.',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_manage_cross_site_feeds(): void
    {
        $admin = $this->makeUser('admin');
        $admin->givePermissionTo('site.manage');

        $site = $this->makeSite('Eko Atlantic Heights');

        // Admin views feed
        $this->actingAs($admin)
            ->get(route('admin.site-feed.index', ['site_id' => $site->id]))
            ->assertOk()
            ->assertSee('Site Operations Feeds');

        // Admin posts announcement
        $this->actingAs($admin)->post(route('admin.site-feed.store'), [
            'site_id' => $site->id,
            'category' => 'urgent',
            'message' => 'Official directive: All team members must inspect emergency exits by 4 PM.',
            'pinned' => 1,
        ])->assertRedirect();

        $msg = SiteMessage::where('site_id', $site->id)->first();
        $this->assertNotNull($msg);
        $this->assertTrue($msg->pinned);

        // Admin deletes message
        $this->actingAs($admin)
            ->delete(route('admin.site-feed.destroy', $msg))
            ->assertRedirect();

        $this->assertDatabaseMissing('site_messages', ['id' => $msg->id]);
    }
}
