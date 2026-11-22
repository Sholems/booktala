@extends('layouts.admin')

@section('page_title', 'Edit Email Campaign')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Campaign: {{ $campaign->title }}</h1>
            <p class="text-xs text-gray-500">Update campaign content or audience targeting before dispatch.</p>
        </div>
        <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Campaign
        </a>
    </div>

    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">
        <strong class="font-bold block mb-1">Please correct the following errors:</strong>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.marketing.campaigns.update', $campaign) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. Campaign Basics -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">1</span>
                Campaign Setup & Envelope
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Internal Campaign Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $campaign->title) }}" required class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Email Subject Line <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject', $campaign->subject) }}" required class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Inbox Preview Preheader Text</label>
                    <input type="text" name="preview_text" value="{{ old('preview_text', $campaign->preview_text) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>
            </div>
        </div>

        <!-- 2. Target Audience Segment -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">2</span>
                Audience Segmentation
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @php $curSegment = old('target_segment', $campaign->target_segment); @endphp
                <label class="border rounded-xl p-4 cursor-pointer hover:border-primary transition flex flex-col justify-between {{ $curSegment === 'all_subscribers' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">All Contacts</span>
                            <span class="text-[11px] text-gray-500">Subscribers & registered guests</span>
                        </div>
                        <input type="radio" name="target_segment" value="all_subscribers" {{ $curSegment === 'all_subscribers' ? 'checked' : '' }} class="mt-1 text-primary">
                    </div>
                    <span class="mt-3 text-xs font-black text-primary">{{ number_format($segmentCounts['all_subscribers']) }} recipients</span>
                </label>

                <label class="border rounded-xl p-4 cursor-pointer hover:border-primary transition flex flex-col justify-between {{ $curSegment === 'past_guests' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">Past Guests</span>
                            <span class="text-[11px] text-gray-500">Guests with completed stays</span>
                        </div>
                        <input type="radio" name="target_segment" value="past_guests" {{ $curSegment === 'past_guests' ? 'checked' : '' }} class="mt-1 text-primary">
                    </div>
                    <span class="mt-3 text-xs font-black text-emerald-600">{{ number_format($segmentCounts['past_guests']) }} recipients</span>
                </label>

                <label class="border rounded-xl p-4 cursor-pointer hover:border-primary transition flex flex-col justify-between {{ $curSegment === 'registered_users' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">Registered Users</span>
                            <span class="text-[11px] text-gray-500">All member accounts</span>
                        </div>
                        <input type="radio" name="target_segment" value="registered_users" {{ $curSegment === 'registered_users' ? 'checked' : '' }} class="mt-1 text-primary">
                    </div>
                    <span class="mt-3 text-xs font-black text-blue-600">{{ number_format($segmentCounts['registered_users']) }} recipients</span>
                </label>

                <label class="border rounded-xl p-4 cursor-pointer hover:border-primary transition flex flex-col justify-between {{ $curSegment === 'active_affiliates' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">Active Affiliates</span>
                            <span class="text-[11px] text-gray-500">{{ (int) \App\Services\AffiliateCommissionService::getDefaultCommissionRate() }}% partner network</span>
                        </div>
                        <input type="radio" name="target_segment" value="active_affiliates" {{ $curSegment === 'active_affiliates' ? 'checked' : '' }} class="mt-1 text-primary">
                    </div>
                    <span class="mt-3 text-xs font-black text-amber-600">{{ number_format($segmentCounts['active_affiliates']) }} partners</span>
                </label>

                <label class="border rounded-xl p-4 cursor-pointer hover:border-primary transition flex flex-col justify-between {{ $curSegment === 'newsletter_only' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">Newsletter Opt-ins</span>
                            <span class="text-[11px] text-gray-500">Subscribed from web banner</span>
                        </div>
                        <input type="radio" name="target_segment" value="newsletter_only" {{ $curSegment === 'newsletter_only' ? 'checked' : '' }} class="mt-1 text-primary">
                    </div>
                    <span class="mt-3 text-xs font-black text-slate-700">{{ number_format($segmentCounts['newsletter_only']) }} subscribers</span>
                </label>
            </div>
        </div>

        <!-- 3. Email Content & Design -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">3</span>
                Editorial Content & Styling
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Template Style</label>
                    <select name="template_type" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none">
                        <option value="luxury_showcase" {{ old('template_type', $campaign->template_type) === 'luxury_showcase' ? 'selected' : '' }}>Luxury Showcase (Featured Villas + Editorial)</option>
                        <option value="promotional_deal" {{ old('template_type', $campaign->template_type) === 'promotional_deal' ? 'selected' : '' }}>Promotional Deal (Discount Voucher Highlight)</option>
                        <option value="concierge_update" {{ old('template_type', $campaign->template_type) === 'concierge_update' ? 'selected' : '' }}>Concierge Update (Text / News / Announcements)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Headline (Header H1)</label>
                    <input type="text" name="headline" value="{{ old('headline', $campaign->headline) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Hero Banner Image URL (Optional)</label>
                    <input type="url" name="hero_image_url" value="{{ old('hero_image_url', $campaign->hero_image_url) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Email Body Content <span class="text-red-500">*</span></label>
                    <textarea name="content_html" rows="7" required class="w-full p-3.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none font-sans leading-relaxed">{{ old('content_html', $campaign->content_html) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Promo Voucher Code (Optional)</label>
                    <input type="text" name="discount_code" value="{{ old('discount_code', $campaign->discount_code) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none font-mono uppercase">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Primary Button Text</label>
                    <input type="text" name="cta_text" value="{{ old('cta_text', $campaign->cta_text) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Primary Button Destination URL</label>
                    <input type="text" name="cta_url" value="{{ old('cta_url', $campaign->cta_url) }}" class="w-full px-3.5 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none">
                </div>
            </div>
        </div>

        <!-- 4. Featured Residences -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-bold">4</span>
                    Featured Apartments in Email
                </span>
                <span class="text-xs text-gray-400 font-normal">Optional showcase cards</span>
            </h2>

            @php $selectedProps = (array) old('featured_property_ids', $campaign->featured_property_ids ?? []); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 overflow-y-auto p-1">
                @foreach($properties as $prop)
                <label class="flex items-center gap-3 p-2.5 border rounded-lg hover:bg-gray-50 cursor-pointer border-gray-200">
                    <input type="checkbox" name="featured_property_ids[]" value="{{ $prop->id }}" {{ in_array($prop->id, $selectedProps) ? 'checked' : '' }} class="text-primary rounded">
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-gray-900 block truncate">{{ $prop->name }}</span>
                        <span class="text-[11px] text-gray-500">{{ $prop->site?->name ?? 'Lagos' }} · ₦{{ number_format($prop->base_price) }}/night</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs rounded-xl shadow transition">
                <i class="fas fa-save mr-1"></i> Update Campaign
            </button>
        </div>
    </form>
</div>
@endsection
