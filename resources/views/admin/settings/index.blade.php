@extends('layouts.admin')
@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
@endphp
@section('page_title', 'Settings')
@section('content')
<h2 class="text-2xl font-bold mb-6">Settings</h2>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold">System Mode</h3>
            <p class="text-sm text-gray-600">Switch between Shortlet Apartment and Hotel management mode</p>
        </div>
        <form method="POST" action="{{ route('admin.settings.toggle-mode') }}">
            @csrf
            <button type="submit" class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors {{ $isHotelMode ? 'bg-accent' : 'bg-gray-300' }}">
                <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition-transform {{ $isHotelMode ? 'translate-x-9' : 'translate-x-1' }}">
                    <i class="fas fa-{{ $isHotelMode ? 'building' : 'home' }} text-xs text-gray-600 flex items-center justify-center h-full"></i>
                </span>
            </button>
            <span class="ml-3 font-medium">{{ $isHotelMode ? 'Hotel Mode' : 'Shortlet Mode' }}</span>
        </form>
    </div>
    @if($isHotelMode)
    <div class="mt-4 p-4 bg-accent/10 border border-accent rounded-lg">
        <p class="text-sm text-accent"><i class="fas fa-info-circle mr-2"></i>Hotel mode enabled. Additional hotel features are now available in the dashboard.</p>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Branding & Logo</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Site Name</label>
                <input type="text" name="site_name" value="{{ \App\Models\Setting::get('site_name') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Logo (Light - for dark backgrounds)</label>
                @if(\App\Models\Setting::get('logo'))
                <div class="mb-2">
                    <img src="{{ url('media/' . ltrim(\App\Models\Setting::get('logo'), '/')) }}" alt="Logo" class="h-16 w-auto object-contain bg-gray-100 p-2 rounded">
                </div>
                @endif
                <input type="file" name="logo" accept="image/*" class="w-full border rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Recommended: 200x60px, PNG with transparency</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Favicon</label>
                @if(\App\Models\Setting::get('favicon'))
                <div class="mb-2">
                    <img src="{{ url('media/' . ltrim(\App\Models\Setting::get('favicon'), '/')) }}" alt="Favicon" class="h-8 w-8 object-contain">
                </div>
                @endif
                <input type="file" name="favicon" accept="image/*" class="w-full border rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Recommended: 32x32px or 64x64px, ICO/PNG</p>
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save Branding</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Social Media Links</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2"><i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook</label>
                <input type="url" name="social_facebook" value="{{ \App\Models\Setting::get('social_facebook') }}" class="w-full border rounded px-3 py-2" placeholder="https://facebook.com/yourpage">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2"><i class="fab fa-twitter text-blue-400 mr-2"></i>Twitter / X</label>
                <input type="url" name="social_twitter" value="{{ \App\Models\Setting::get('social_twitter') }}" class="w-full border rounded px-3 py-2" placeholder="https://twitter.com/yourhandle">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2"><i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram</label>
                <input type="url" name="social_instagram" value="{{ \App\Models\Setting::get('social_instagram') }}" class="w-full border rounded px-3 py-2" placeholder="https://instagram.com/yourhandle">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2"><i class="fab fa-linkedin text-blue-700 mr-2"></i>LinkedIn</label>
                <input type="url" name="social_linkedin" value="{{ \App\Models\Setting::get('social_linkedin') }}" class="w-full border rounded px-3 py-2" placeholder="https://linkedin.com/company/yourcompany">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2"><i class="fab fa-youtube text-red-600 mr-2"></i>YouTube</label>
                <input type="url" name="social_youtube" value="{{ \App\Models\Setting::get('social_youtube') }}" class="w-full border rounded px-3 py-2" placeholder="https://youtube.com/@yourchannel">
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save Social Links</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">General Settings</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Contact Email</label>
                <input type="email" name="email" value="{{ \App\Models\Setting::get('email') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Phone Number</label>
                <input type="text" name="phone" value="{{ \App\Models\Setting::get('phone') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">VAT Rate (%)</label>
                <input type="number" name="vat_rate" value="{{ \App\Models\Setting::get('vat_rate', 7.5) }}" step="0.1" class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save Settings</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Paystack Payment Configuration</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Paystack Public Key</label>
                <input type="text" name="paystack_public_key" value="{{ \App\Models\Setting::get('paystack_public_key') }}" class="w-full border rounded px-3 py-2" placeholder="pk_live_...">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Paystack Secret Key</label>
                <input type="password" name="paystack_secret_key" value="{{ \App\Models\Setting::get('paystack_secret_key') }}" class="w-full border rounded px-3 py-2" placeholder="sk_live_...">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Paystack Merchant Email</label>
                <input type="email" name="paystack_merchant_email" value="{{ \App\Models\Setting::get('paystack_merchant_email') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="paystack_live_mode" value="1" {{ \App\Models\Setting::get('paystack_live_mode') ? 'checked' : '' }} class="mr-2">
                    <span class="text-sm font-medium">Live Mode (uncheck for test mode)</span>
                </label>
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save Paystack Settings</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
        <h3 class="text-lg font-semibold mb-4">Email Configuration</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Driver</label>
                    <select name="mail_driver" class="w-full border rounded px-3 py-2">
                        <option value="smtp" {{ \App\Models\Setting::get('mail_driver') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="sendmail" {{ \App\Models\Setting::get('mail_driver') == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        <option value="mailgun" {{ \App\Models\Setting::get('mail_driver') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Host</label>
                    <input type="text" name="mail_host" value="{{ \App\Models\Setting::get('mail_host') }}" class="w-full border rounded px-3 py-2" placeholder="smtp.mailtrap.io">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Port</label>
                    <input type="number" name="mail_port" value="{{ \App\Models\Setting::get('mail_port', 587) }}" class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Username</label>
                    <input type="text" name="mail_username" value="{{ \App\Models\Setting::get('mail_username') }}" class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Password</label>
                    <input type="password" name="mail_password" value="{{ \App\Models\Setting::get('mail_password') }}" class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Mail Encryption</label>
                    <select name="mail_encryption" class="w-full border rounded px-3 py-2">
                        <option value="tls" {{ \App\Models\Setting::get('mail_encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ \App\Models\Setting::get('mail_encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ \App\Models\Setting::get('mail_encryption') == '' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">From Address</label>
                    <input type="email" name="mail_from_address" value="{{ \App\Models\Setting::get('mail_from_address') }}" class="w-full border rounded px-3 py-2" placeholder="noreply@example.com">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">From Name</label>
                    <input type="text" name="mail_from_name" value="{{ \App\Models\Setting::get('mail_from_name', 'BookTala') }}" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save Email Settings</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
        <h3 class="text-lg font-semibold mb-4">SMS Configuration (Termii)</h3>
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Termii API Key</label>
                    <input type="text" name="termii_api_key" value="{{ \App\Models\Setting::get('termii_api_key') }}" class="w-full border rounded px-3 py-2" placeholder="Your Termii API key">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Sender ID</label>
                    <input type="text" name="termii_sender_id" value="{{ \App\Models\Setting::get('termii_sender_id', 'BOOKTALA') }}" class="w-full border rounded px-3 py-2" placeholder="BOOKTALA">
                </div>
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Save SMS Settings</button>
        </form>
    </div>
</div>
@endsection
