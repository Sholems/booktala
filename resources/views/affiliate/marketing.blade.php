@extends('layouts.affiliate')

@section('title', 'Marketing Toolkit')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900">Marketing & Promotion Toolkit</h1>
        <p class="text-xs md:text-sm text-gray-500">Ready-made copy, pitch templates, and assets to share with your clients and audience.</p>
    </div>

    <!-- WhatsApp & Social Copy Templates -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Pitch 1: Diaspora & Holiday Travelers -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i class="fab fa-whatsapp text-emerald-500 text-xl"></i>
                    <h3 class="font-bold text-gray-900">WhatsApp Pitch: Diaspora & Holiday Stays</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">Targeted at friends, family, and overseas clients visiting Lagos.</p>

                <div class="bg-gray-50 p-4 rounded-xl text-xs font-mono text-gray-800 leading-relaxed border border-gray-200 select-all mb-4" id="pitchDiaspora">
Visiting Lagos soon? Don't risk unreliable electricity or fake Airbnb photos. Check out BookTala—verified luxury serviced apartments in Lekki Phase 1, Victoria Island & Ikoyi with GUARANTEED 24/7 power, high-speed fiber WiFi, and 24/7 concierge service.

Browse verified units here:
{{ $affiliate->referral_url }}
                </div>
            </div>

            <button onclick="copyText('pitchDiaspora')" class="w-full py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-copy"></i> Copy WhatsApp Pitch
            </button>
        </div>

        <!-- Pitch 2: Corporate & Relocation Clients -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-briefcase text-primary text-xl"></i>
                    <h3 class="font-bold text-gray-900">Email Pitch: Corporate & Business Travelers</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">Targeted at corporate admins, consultants, and executive expats.</p>

                <div class="bg-gray-50 p-4 rounded-xl text-xs font-mono text-gray-800 leading-relaxed border border-gray-200 select-all mb-4" id="pitchCorporate">
Hi [Name],

If you or your team have upcoming business trips to Lagos, I recommend BookTala for serviced shortlets in Lekki and Victoria Island. Every apartment features dedicated fiber internet for Zoom/remote work, dual industrial generators ensuring 0% power downtime, and official digital tax receipts for company expense reporting.

Book directly here:
{{ $affiliate->referral_url }}
                </div>
            </div>

            <button onclick="copyText('pitchCorporate')" class="w-full py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition flex items-center justify-center gap-2">
                <i class="fas fa-copy"></i> Copy Corporate Pitch
            </button>
        </div>
    </div>

    <!-- Top Featured Apartments Grid for Sharing -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h3 class="font-bold text-lg text-gray-900 mb-1 flex items-center gap-2">
            <i class="fas fa-hotel text-amber-500"></i> Top Converting Apartments to Promote
        </h3>
        <p class="text-xs text-gray-500 mb-6">Each apartment card includes your partner tracking link automatically.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($properties as $prop)
            <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition">
                <div class="h-40 bg-gray-200 relative">
                    @if($prop->primaryImage)
                    <img src="{{ $prop->primaryImage->image_path }}" alt="{{ $prop->title }}" class="w-full h-full object-cover">
                    @endif
                    <span class="absolute bottom-2 right-2 bg-black/70 backdrop-blur-sm text-white text-[11px] font-bold px-2 py-0.5 rounded">
                        &#8358;{{ number_format($prop->price_per_night) }}/night
                    </span>
                </div>
                <div class="p-4">
                    <h4 class="font-bold text-sm text-gray-900 truncate mb-1">{{ $prop->title }}</h4>
                    <p class="text-xs text-gray-500 mb-3">{{ $prop->city }}, Lagos</p>
                    <div class="flex items-center gap-2">
                        <button onclick="copyCustomUrl('{{ route('properties.show', $prop->slug ?: $prop->id) }}?ref={{ $affiliate->code }}')" class="w-full py-1.5 bg-gray-100 hover:bg-primary hover:text-white text-gray-700 rounded-lg text-xs font-semibold transition">
                            <i class="fas fa-link mr-1"></i> Copy Link
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 5 Rules & Program FAQ -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h3 class="font-bold text-lg text-gray-900 mb-4">Affiliate Program Rules & Policies</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <strong class="text-gray-900 block mb-1">5% Net Room Rate Commission</strong>
                Commissions are calculated on net accommodation charges only. Caution deposits, processing fees, and taxes are excluded.
            </div>
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <strong class="text-gray-900 block mb-1">24-Hour Post Check-Out Maturity</strong>
                Commissions mature 24 hours after verified guest check-out to protect against cancellations or refunds.
            </div>
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <strong class="text-gray-900 block mb-1">30-Day Cookie Window</strong>
                When a visitor clicks your link, your partner code is saved in their browser for 30 days. Any booking they make earns you credit.
            </div>
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <strong class="text-gray-900 block mb-1">₦20,000 Minimum Payout</strong>
                Withdrawals can be requested once your available balance reaches ₦20,000, paid to any Nigerian commercial bank.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyText(elementId) {
        const text = document.getElementById(elementId).innerText.trim();
        navigator.clipboard.writeText(text);
        alert('Pitch copied to clipboard! You can paste it into WhatsApp or Email.');
    }

    function copyCustomUrl(url) {
        navigator.clipboard.writeText(url);
        alert('Apartment referral link copied to clipboard!');
    }
</script>
@endpush
@endsection
