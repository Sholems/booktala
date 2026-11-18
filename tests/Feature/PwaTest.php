<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The installable app: manifest, icons, service worker and offline page.
 * (Service worker behaviour itself was checked in a real browser.)
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_installable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifest, 'manifest.webmanifest must be valid JSON');
        foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'theme_color', 'background_color', 'icons'] as $key) {
            $this->assertArrayHasKey($key, $manifest);
        }
        $this->assertSame('standalone', $manifest['display']);

        $sizes = collect($manifest['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($manifest['icons'])->pluck('purpose')->all());

        // Every referenced icon exists and has the size it claims.
        $icons = array_merge($manifest['icons'], ...array_column($manifest['shortcuts'] ?? [], 'icons'));
        foreach ($icons as $icon) {
            $path = public_path($icon['src']);
            $this->assertFileExists($path);
            [$w, $h] = getimagesize($path);
            $this->assertSame($icon['sizes'], "{$w}x{$h}", $icon['src']);
        }
    }

    public function test_service_worker_precaches_offline_page_and_keeps_private_areas_out(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString("url('offline.html')", $sw);
        foreach (['admin', 'staff', 'customer', 'login', 'booking'] as $private) {
            $this->assertStringContainsString("'{$private}'", $sw, "{$private} pages must never be cached");
        }
        foreach (['images/booktala-logo.png', 'icons/icon-192.png', 'icons/icon-512.png'] as $file) {
            $this->assertStringContainsString($file, $sw);
            $this->assertFileExists(public_path($file));
        }
    }

    public function test_every_portal_links_the_manifest_and_registers_the_worker(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $pages = [
            [null, '/'],
            [null, '/apartments'],
            [null, '/login'],
            ['customer', '/customer/dashboard'],
            ['staff', '/staff/dashboard'],
            ['admin', '/admin/dashboard'],
        ];

        foreach ($pages as [$role, $path]) {
            if ($role) {
                $user = User::create(['name' => ucfirst($role), 'email' => $role.'@example.com', 'password' => 'password123', 'status' => 'active']);
                $user->assignRole($role);
                $this->actingAs($user);
            }

            $this->get($path)
                ->assertOk()
                ->assertSee('rel="manifest"', false)
                ->assertSee('manifest.webmanifest', false)
                ->assertSee('apple-touch-icon', false)
                ->assertSee('serviceWorker.register', false);

            auth()->logout();
        }
    }
}
