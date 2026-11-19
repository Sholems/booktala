<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\Setting;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Credentials are never sent back to the browser. The form shows whether
     * one is saved; leaving the field blank keeps it.
     */
    public const SECRET_FIELDS = ['paystack_secret_key', 'mail_password', 'termii_api_key'];

    public function index()
    {
        $savedSecrets = collect(self::SECRET_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => filled(Setting::get($field))]);

        return view('admin.settings.index', compact('savedSecrets'));
    }

    public function update(SettingsUpdateRequest $request)
    {
        $request->validated();

        $fields = [
            'site_name', 'email', 'phone', 'vat_rate',
            'paystack_public_key', 'paystack_secret_key', 'paystack_merchant_email',
            'mail_driver', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
            'termii_api_key', 'termii_sender_id',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube',
        ];

        foreach ($fields as $field) {
            if (! $request->has($field)) {
                continue;
            }
            // A blank secret means "keep the saved one".
            if (in_array($field, self::SECRET_FIELDS, true) && ! $request->filled($field)) {
                continue;
            }
            Setting::set($field, $request->input($field));
        }

        Setting::set('paystack_live_mode', $request->has('paystack_live_mode'));

        // Branding images are public: they go on the public disk that /media serves.
        foreach (['logo', 'favicon'] as $key) {
            if ($request->hasFile($key)) {
                $old = Setting::get($key);
                Setting::set($key, $request->file($key)->store('settings', 'public'));
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }

        return back()->with('success', 'Settings updated successfully');
    }

    public function toggleMode(Request $request)
    {
        $currentMode = SystemSetting::get('hotel_mode', false);
        $newMode = ! $currentMode;

        SystemSetting::set('hotel_mode', $newMode);

        $mode = $newMode ? 'Hotel' : 'Shortlet Apartment';

        return back()->with('success', "System switched to {$mode} mode");
    }
}
