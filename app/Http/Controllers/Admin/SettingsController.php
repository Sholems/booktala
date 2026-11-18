<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $fields = [
            'site_name', 'email', 'phone', 'vat_rate',
            'paystack_public_key', 'paystack_secret_key', 'paystack_merchant_email',
            'mail_driver', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
            'termii_api_key', 'termii_sender_id',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
            }
        }

        if ($request->has('paystack_live_mode')) {
            Setting::set('paystack_live_mode', true);
        } else {
            Setting::set('paystack_live_mode', false);
        }

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo')->store('public/settings');
            Setting::set('logo', str_replace('public/', '', $logo));
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon')->store('public/settings');
            Setting::set('favicon', str_replace('public/', '', $favicon));
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
