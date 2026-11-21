@extends('layouts.affiliate')

@section('title', 'Partner Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Top Welcome Banner & Referral Code Box -->
    <div class="bg-gradient-to-r from-[#0B2D5B] to-[#123E7A] text-white p-6 md:p-8 rounded-2xl shadow-lg flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <span class="text-xs uppercase font-bold tracking-wider text-amber-400 bg-white/10 px-3 py-1 rounded-full border border-white/15 inline-block mb-2">
                5% Direct Partner
            </span>
            <h1 class="text-2xl md:text-3xl font-black">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-gray-200 mt-1">Share your link with your network and track your 5% commissions in real time.</p>
        </div>

        <!-- Quick Copy Box -->
        <div class="w-full md:w-auto bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 shrink-0">
            <span class="text-[11px] font-bold text-gray-300 uppercase tracking-wider block mb-1.5">Your Master Referral Link</span>
            <div class="flex items-center gap-2">
                <input type="text" id="masterReferralLink" readonly value="{{ $affiliate->referral_url }}" class="bg-black/30 text-white font-mono text-xs px-3 py-2 rounded-lg border border-white/20 w-full sm:w-64 focus:outline-none select-all">
                <button onclick="copyMasterLink()" class="px-3.5 py-2 bg-amber-400 text-gray-900 rounded-lg text-xs font-bold hover:bg-amber-300 transition flex items-center gap-1.5 shrink-0 shadow">
                    <i class="fas fa-copy"></i> <span id="copyBtnText">Copy</span>
                </button>
                <a href="#partnerQrSection" class="px-3 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shrink-0" title="View & Download QR Code">
                    <i class="fas fa-qrcode text-amber-300"></i> <span>QR</span>
                </a>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-300">
                <span>Partner Code: <strong class="text-amber-300 font-mono">{{ $affiliate->code }}</strong></span>
                <span class="text-emerald-400"><i class="fas fa-check-circle"></i> 30-Day Cookie Active</span>
            </div>
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <!-- Available Balance -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Available Balance</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900">&#8358;{{ number_format($affiliate->current_balance) }}</p>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="text-emerald-600 font-medium">Ready for withdrawal</span>
                <a href="{{ route('affiliate.payouts') }}" class="text-primary font-bold hover:underline">Withdraw →</a>
            </div>
        </div>

        <!-- Pending In Escrow -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Pending (In Escrow)</span>
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900">&#8358;{{ number_format($affiliate->pending_balance) }}</p>
            <p class="text-xs text-gray-500 mt-2">Matures 24h post guest check-out</p>
        </div>

        <!-- Lifetime Earnings -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Lifetime Earnings</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 text-primary flex items-center justify-center text-sm">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900">&#8358;{{ number_format($affiliate->lifetime_earnings) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $affiliate->completed_referrals_count }} stay(s) fulfilled</p>
        </div>

        <!-- Clicks & Conversion -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Traffic & Conversion</span>
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fas fa-mouse-pointer"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900">{{ $clicksCount }} <span class="text-sm font-semibold text-gray-500">clicks</span></p>
            <p class="text-xs text-purple-700 font-semibold mt-2">{{ $conversionRate }}% booking conversion</p>
        </div>
    </div>

    <!-- Share Tools: QR Code & Deep Link Generator Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- QR Code Card (5 cols) -->
        <div id="partnerQrSection" class="lg:col-span-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-lg text-primary flex items-center gap-2">
                        <i class="fas fa-qrcode text-amber-500"></i> Partner Referral QR Code
                    </h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary px-2 py-0.5 rounded font-mono">
                        {{ $affiliate->code }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 mb-4">
                    Have clients scan this code with their smartphone camera to open BookTala with your 5% attribution locked in for 30 days.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100 mb-4">
                    <div class="bg-white p-2.5 rounded-xl shadow-sm border border-gray-200 shrink-0 flex items-center justify-center">
                        <canvas class="js-qr block" data-url="{{ $affiliate->referral_url }}" width="140" height="140"></canvas>
                    </div>
                    <div class="text-xs text-gray-600 space-y-1.5 text-center sm:text-left">
                        <p><strong class="text-gray-900 block">30-Day Cookie Lock</strong> Any booking made within 30 days is credited to you.</p>
                        <p class="text-[11px] text-gray-500">Ideal for business cards, brochures, WhatsApp status, and client meetings.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="downloadQrCode()" class="w-full py-2 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fas fa-download"></i> <span>Download PNG</span>
                </button>
                <a href="https://api.whatsapp.com/send?text={{ urlencode("Looking for luxury shortlet apartments in Lagos with guaranteed 24/7 power? Browse BookTala's verified residences here: " . $affiliate->referral_url) }}" target="_blank" rel="noopener noreferrer" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fab fa-whatsapp"></i> <span>WhatsApp</span>
                </a>
                <a href="{{ route('affiliate.flyer') }}" class="col-span-2 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 border border-gray-200">
                    <i class="fas fa-print text-primary"></i> <span>Print Flyer &amp; Table-Tent Card</span>
                </a>
            </div>
        </div>

        <!-- Apartment Deep-Link Generator Box (7 cols) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-lg text-primary mb-1 flex items-center gap-2">
                    <i class="fas fa-link text-accent"></i> Apartment Deep-Link Generator
                </h3>
                <p class="text-xs text-gray-600 mb-4">Promoting a specific penthouse or luxury apartment? Select it below to generate a direct tracked link for your followers.</p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Select Property</label>
                        <select id="apartmentSelector" onchange="generateApartmentLink()" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                            <option value="">-- Select an Apartment --</option>
                            @foreach($properties as $prop)
                            <option value="{{ route('properties.show', $prop->slug ?: $prop->id) }}">
                                {{ $prop->title }} (&#8358;{{ number_format($prop->price_per_night) }}/night)
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Your Custom Tracked Link</label>
                        <div class="flex gap-2">
                            <input type="text" id="customApartmentLink" readonly placeholder="Select an apartment to generate link" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-700">
                            <button onclick="copyApartmentLink()" class="px-5 py-2.5 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 shrink-0">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 flex items-center gap-2 mt-4">
                <i class="fas fa-lightbulb text-amber-600"></i>
                <span>Tip: Guests landing on any single apartment still have your 30-day cookie applied across the entire site!</span>
            </div>
        </div>
    </div>

    <!-- Recent Referrals Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Recent Referred Stays</h3>
                <p class="text-xs text-gray-500">Your most recent customer bookings</p>
            </div>
            <a href="{{ route('affiliate.referrals') }}" class="text-xs font-bold text-primary hover:underline">
                View All Referrals →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-4">Stay Dates</th>
                        <th class="p-4">Apartment</th>
                        <th class="p-4">Room Base</th>
                        <th class="p-4">Your 5% Commission</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentReferrals as $ref)
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-4">
                            <span class="font-semibold text-gray-900">{{ $ref->check_in->format('M d') }} – {{ $ref->check_out->format('M d, Y') }}</span>
                            <span class="block text-[11px] text-gray-500">Ref: {{ $ref->reservation->booking_ref }}</span>
                        </td>
                        <td class="p-4 font-medium text-gray-900">
                            {{ $ref->reservation->property->title ?? 'Apartment' }}
                        </td>
                        <td class="p-4 font-mono font-medium text-gray-700">
                            &#8358;{{ number_format($ref->base_accommodation_amount) }}
                        </td>
                        <td class="p-4 font-mono font-bold text-emerald-600">
                            +&#8358;{{ number_format($ref->commission_amount) }}
                        </td>
                        <td class="p-4">
                            @if($ref->status === 'approved')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Matured
                            </span>
                            @elseif($ref->status === 'paid')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                Paid Out
                            </span>
                            @elseif($ref->status === 'void')
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                Void (Cancelled)
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800" title="Matures 24h post checkout">
                                Pending Stay
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl text-gray-300 mb-2 block"></i>
                            No referred bookings recorded yet. Share your master referral link to get started!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyMasterLink() {
        const input = document.getElementById('masterReferralLink');
        input.select();
        navigator.clipboard.writeText(input.value);
        document.getElementById('copyBtnText').innerText = 'Copied!';
        setTimeout(() => {
            document.getElementById('copyBtnText').innerText = 'Copy';
        }, 2000);
    }

    function downloadQrCode() {
        const canvas = document.querySelector('canvas.js-qr');
        if (!canvas) {
            alert('QR code is still rendering. Please try again in a moment.');
            return;
        }
        const link = document.createElement('a');
        link.download = 'BookTala-Affiliate-{{ $affiliate->code }}-QR.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    function generateApartmentLink() {
        const select = document.getElementById('apartmentSelector');
        const linkInput = document.getElementById('customApartmentLink');
        if (!select.value) {
            linkInput.value = '';
            return;
        }
        const partnerCode = @json($affiliate->code);
        linkInput.value = select.value + (select.value.includes('?') ? '&' : '?') + 'ref=' + encodeURIComponent(partnerCode);
    }

    function copyApartmentLink() {
        const linkInput = document.getElementById('customApartmentLink');
        if (!linkInput.value) return;
        linkInput.select();
        navigator.clipboard.writeText(linkInput.value);
        alert('Custom apartment tracking link copied to clipboard!');
    }
</script>
@endpush
@endsection
