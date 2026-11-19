<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PagesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PageSeeder::class);
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

        return $user;
    }

    public function test_public_can_view_detailed_terms_and_conditions(): void
    {
        $response = $this->get(route('pages.terms'));

        $response->assertStatus(200);
        $response->assertSee('Terms and Conditions');
        $response->assertSee('Paystack');
        $response->assertSee('Caution');
        $response->assertSee('Smoke-Free');
    }

    public function test_public_can_view_detailed_privacy_policy(): void
    {
        $response = $this->get(route('pages.privacy'));

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Nigeria Data Protection Act');
        $response->assertSee('Paystack');
        $response->assertSee('Right to Erasure');
    }

    public function test_public_can_view_detailed_cancellation_policy(): void
    {
        $response = $this->get(route('pages.cancellation'));

        $response->assertStatus(200);
        $response->assertSee('Cancellation &amp; Refund Policy', false);
        $response->assertSee('72 hours');
        $response->assertSee('Caution deposits are 100% refundable');
    }

    public function test_public_can_view_house_rules_page(): void
    {
        $response = $this->get(url('/house-rules'));

        $response->assertStatus(200);
        $response->assertSee('House Rules &amp; Resident Guidelines', false);
        $response->assertSee('10:00 PM and 7:00 AM');
        $response->assertSee('50,000');
    }

    public function test_public_can_view_security_deposit_policy_page(): void
    {
        $response = $this->get(url('/security-deposit-policy'));

        $response->assertStatus(200);
        $response->assertSee('Security &amp; Caution Deposit Policy', false);
        $response->assertSee('Prompt Refund Release', false);
    }

    public function test_public_can_view_faq_page(): void
    {
        $response = $this->get(route('pages.faq'));

        $response->assertStatus(200);
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Paystack');
        $response->assertSee('24/7');
    }

    public function test_public_can_view_owner_partnership_terms_page(): void
    {
        $response = $this->get(url('/owner-partnership-terms'));

        $response->assertStatus(200);
        $response->assertSee('Property Owner &amp; Host Partnership Terms', false);
        $response->assertSee('Revenue-Share');
        $response->assertSee('Owner Personal Stay Allotments');
    }

    public function test_admin_can_view_pages_list_in_dashboard(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('admin.pages.index'));

        $response->assertStatus(200);
        $response->assertSee('Website Pages &amp; Policies', false);
        $response->assertSee('terms-and-conditions');
        $response->assertSee('privacy-policy');
        $response->assertSee('cancellation-policy');
        $response->assertSee('house-rules');
        $response->assertSee('security-deposit-policy');
    }

    public function test_admin_can_edit_and_update_page_content(): void
    {
        $admin = $this->makeUser('admin');
        $page = Page::where('slug', 'terms-and-conditions')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('admin.pages.update', $page->id), [
            'title' => 'Updated Terms of Service 2026',
            'slug' => 'terms-and-conditions',
            'content' => '<h2>Custom Updated Terms</h2><p>This is customized policy content directly from the admin dashboard.</p>',
            'meta_title' => 'Updated Terms of Service - BookTala',
            'meta_description' => 'Updated legal terms and conditions.',
            'is_published' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $page->refresh();
        $this->assertEquals('Updated Terms of Service 2026', $page->title);
        $this->assertStringContainsString('Custom Updated Terms', $page->content);
        $this->assertTrue($page->is_published);

        // Verify the public page reflects the updated content immediately
        $publicResponse = $this->get(route('pages.terms'));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Custom Updated Terms');
    }

    public function test_admin_can_create_new_custom_page(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Sustainability & Green Living',
            'slug' => 'sustainability-policy',
            'content' => '<h2>Our Eco Commitment</h2><p>We use solar inverters and energy-efficient appliances.</p>',
            'meta_title' => 'Sustainability at BookTala',
            'meta_description' => 'How we preserve energy and reduce carbon footprints.',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'slug' => 'sustainability-policy',
            'title' => 'Sustainability & Green Living',
            'is_published' => 1,
        ]);

        // Verify newly created page is reachable by slug
        $publicResponse = $this->get(url('/sustainability-policy'));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Our Eco Commitment');
    }

    public function test_admin_can_delete_page(): void
    {
        $admin = $this->makeUser('admin');
        $page = Page::create([
            'title' => 'Temporary Promo Terms',
            'slug' => 'temporary-promo-terms',
            'content' => '<p>Short promo details</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.pages.destroy', $page->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_guests_cannot_access_admin_pages_management(): void
    {
        $guest = $this->makeUser('customer');

        $this->actingAs($guest)->get(route('admin.pages.index'))->assertStatus(403);
        $this->actingAs($guest)->get(route('admin.pages.create'))->assertStatus(403);
    }
}
