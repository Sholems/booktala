<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\Property;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use App\Support\PropertyAccess;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_assigned_user_needs_explicit_portfolio_permission_to_see_all_units(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $lekki = Site::create(['name' => 'Lekki', 'address' => '1 Road', 'city' => 'Lagos', 'state' => 'Lagos']);
        $abuja = Site::create(['name' => 'Abuja', 'address' => '2 Road', 'city' => 'Abuja', 'state' => 'FCT']);
        $lekkiUnit = $this->makeProperty('Lekki Unit', $lekki);
        $abujaUnit = $this->makeProperty('Abuja Unit', $abuja);
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('staff');
        $user->givePermissionTo('property.view');
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'S-'.$user->id, 'hire_date' => today()]);
        $staff->assignTo($lekki, Position::where('name', 'Receptionist')->firstOrFail());

        $this->assertTrue(PropertyAccess::canAccess($user, $lekkiUnit));
        $this->assertFalse(PropertyAccess::canAccess($user, $abujaUnit));

        $user->givePermissionTo('portfolio.view_all');

        $this->assertTrue(PropertyAccess::canAccess($user->fresh(), $abujaUnit));
    }

    private function makeProperty(string $title, Site $site): Property
    {
        return Property::create([
            'title' => $title, 'description' => 'Test apartment', 'address' => '1 Road', 'city' => $site->city,
            'state' => $site->state, 'price_per_night' => 10000, 'max_guests' => 2, 'bedrooms' => 1,
            'bathrooms' => 1, 'toilets' => 1, 'status' => 'active', 'site_id' => $site->id,
        ]);
    }
}
