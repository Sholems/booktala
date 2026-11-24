<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Setting;
use App\Models\User;
use App\Support\SafeHtml;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fixes from the November 2026 code review: private ID documents, settings
 * secrets, the channels permission, public storage routing, logo uploads and
 * sanitised rich text.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::create(['name' => ucfirst($role).' '.uniqid(), 'email' => $role.uniqid().'@example.com', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    // --- Guest ID documents ----------------------------------------------------------------

    public function test_guest_id_documents_are_private_and_only_staff_who_manage_customers_can_open_them(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $guest = $this->makeUser('customer');

        $this->actingAs($guest)->post(route('customer.profile.upload-id'), [
            'id_document' => UploadedFile::fake()->image('passport.jpg'),
        ])->assertSessionHas('success');

        $path = $guest->fresh()->id_document;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->get('/media/'.$path)->assertNotFound();
        $this->get('/storage/'.$path)->assertNotFound();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('admin.customers.show', $guest))->assertOk()->assertSee('View ID');
        $this->actingAs($admin)->get(route('admin.customers.id-document', $guest))->assertOk()->assertHeader('cache-control', 'no-store, private');

        $this->actingAs($this->makeUser('customer'))->get(route('admin.customers.id-document', $guest))->assertForbidden();
        $this->actingAs($this->makeUser('staff'))->get(route('admin.customers.id-document', $guest))->assertForbidden();

        // Replacing the document removes the old file.
        $this->actingAs($guest)->post(route('customer.profile.upload-id'), ['id_document' => UploadedFile::fake()->image('new.jpg')]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_existing_public_id_documents_are_moved_to_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $guest = $this->makeUser('customer');
        Storage::disk('public')->put('id-documents/old.jpg', 'scan');
        $guest->update(['id_document' => 'id-documents/old.jpg']);

        (require database_path('migrations/2026_12_02_000001_secure_uploaded_files.php'))->up();

        Storage::disk('public')->assertMissing('id-documents/old.jpg');
        $this->assertSame('scan', Storage::disk('local')->get('id-documents/old.jpg'));
    }

    public function test_private_r2_documents_are_never_served_by_public_media_routes(): void
    {
        config(['filesystems.default' => 'r2']);
        Storage::fake('r2');
        Storage::fake('public');
        $guest = $this->makeUser('customer');

        $this->actingAs($guest)->post(route('customer.profile.upload-id'), [
            'id_document' => UploadedFile::fake()->image('passport.jpg'),
        ])->assertSessionHas('success');

        $path = $guest->fresh()->id_document;
        Storage::disk('r2')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->get('/media/'.$path)->assertNotFound();
        $this->get('/storage/'.$path)->assertNotFound();
    }

    public function test_customer_admin_actions_only_touch_customer_accounts(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');
        $guest = $this->makeUser('customer');

        $this->actingAs($admin)->get(route('admin.customers.show', $staff))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.customers.status', $staff), ['status' => 'suspended'])->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.customers.status', $guest), ['status' => 'hacked'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->patch(route('admin.customers.verify', $guest))->assertSessionHas('error');
        $this->assertFalse((bool) $guest->fresh()->id_verified);
    }

    // --- Settings and channels -------------------------------------------------------------

    public function test_admins_manage_channels_but_not_settings_and_secrets_never_reach_the_browser(): void
    {
        $admin = $this->makeUser('admin');
        $superAdmin = $this->makeUser('super_admin');
        Setting::set('paystack_secret_key', 'sk_live_supersecretvalue123');
        Setting::set('mail_password', 'mail-pass-9876');

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.channel-manager.index'))->assertOk();

        $this->actingAs($superAdmin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertDontSee('sk_live_supersecretvalue123')
            ->assertDontSee('mail-pass-9876')
            ->assertSee('Saved. Leave blank to keep it');

        // Blank keeps the saved secret; a new value replaces it.
        $this->actingAs($superAdmin)->post(route('admin.settings.update'), ['paystack_secret_key' => '', 'site_name' => 'BookTala'])->assertSessionHas('success');
        $this->assertSame('sk_live_supersecretvalue123', Setting::get('paystack_secret_key'));
        $this->actingAs($superAdmin)->post(route('admin.settings.update'), ['paystack_secret_key' => 'sk_live_rotated'])->assertSessionHas('success');
        $this->assertSame('sk_live_rotated', Setting::get('paystack_secret_key'));
    }

    public function test_uploaded_logo_is_served_from_the_public_media_route(): void
    {
        Storage::fake('public');
        $superAdmin = $this->makeUser('super_admin');

        $this->actingAs($superAdmin)->post(route('admin.settings.update'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ])->assertSessionHasNoErrors();

        $logo = Setting::get('logo');
        Storage::disk('public')->assertExists($logo);
        $this->get('/media/'.$logo)->assertOk();

        $this->actingAs($superAdmin)->post(route('admin.settings.update'), [
            'logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_public_storage_urls_are_served_by_the_app_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/me.jpg', 'img');
        Storage::disk('public')->put('receipts/private.jpg', 'receipt');
        Storage::disk('public')->put('id-documents/private.jpg', 'identity');
        Storage::disk('public')->put('housekeeping_inspections/private.jpg', 'inspection');

        $this->get('/storage/avatars/me.jpg')->assertOk();
        $this->get('/storage/avatars/missing.jpg')->assertNotFound();
        $this->get('/storage/receipts/private.jpg')->assertNotFound();
        $this->get('/storage/id-documents/private.jpg')->assertNotFound();
        $this->get('/storage/housekeeping_inspections/private.jpg')->assertNotFound();
    }

    // --- Rich text ------------------------------------------------------------------------------

    public function test_rich_text_keeps_formatting_but_drops_scripts(): void
    {
        $dirty = '<h2 class="ql-align-center">Welcome</h2><p style="color: red">Hello <strong>guest</strong></p>'
            .'<script>alert(1)</script><img src="/x.jpg" onerror="alert(2)">'
            .'<a href="javascript:alert(3)">bad</a><a href="https://example.com">good</a><iframe src="https://evil.test"></iframe>';

        $clean = SafeHtml::clean($dirty);

        $this->assertStringContainsString('<h2 class="ql-align-center">Welcome</h2>', $clean);
        $this->assertStringContainsString('<strong>guest</strong>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
    }

    public function test_blog_posts_are_sanitised_on_the_public_site(): void
    {
        $blog = Blog::create([
            'title' => 'Safety first',
            'slug' => 'safety-first',
            'content' => '<p>Read me</p><script>document.cookie</script>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('blog.show', $blog->slug))->assertOk()->assertSee('Read me')->assertDontSee('<script>document.cookie</script>', false);
    }

    public function test_unpublished_blog_drafts_are_not_public(): void
    {
        Blog::create(['title' => 'Draft', 'slug' => 'secret-draft', 'content' => '<p>Not yet</p>', 'is_published' => false]);

        $this->get(route('blog.show', 'secret-draft'))->assertNotFound();
    }
}
