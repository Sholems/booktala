@extends('layouts.app')

@php
    $rate = $defaultRate ?? \App\Services\AffiliateCommissionService::getDefaultCommissionRate();
    $rateInt = (int) $rate;
    $rateFormatted = ($rate == $rateInt) ? $rateInt : number_format($rate, 1);
@endphp

@section('title', "BookTala Affiliate & Partner Program — Earn {$rateFormatted}% Flat Commission")

@section('content')
<!-- Hero Section -->
<section class="py-16 md:py-20 text-white text-center relative overflow-hidden" style="background-color: #0B2D5B; background: linear-gradient(135deg, #0B2D5B 0%, #0D3870 50%, #082042 100%);">
    <div class="absolute -right-24 -bottom-24 w-96 h-96 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -left-24 -top-24 w-96 h-96 rounded-full bg-blue-400/10 blur-3xl pointer-events-none"></div>

    <div class="container mx-auto px-4 max-w-4xl relative z-10">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-amber-400 text-xs md:text-sm font-bold uppercase tracking-wider mb-5 border border-white/15 backdrop-blur-sm">
            <i class="fas fa-handshake"></i> BookTala Partner &amp; Affiliate Program
        </span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight mb-5 leading-tight">
            Earn <span class="text-amber-400 underline decoration-amber-400/40 underline-offset-8">{{ $rateFormatted }}% Commission</span> on Every Shortlet Booking
        </h1>
        <p class="text-base sm:text-lg md:text-xl text-gray-200 max-w-2xl mx-auto leading-relaxed mb-8">
            Turn your real estate network, diaspora audience, and corporate connections into reliable monthly income. Zero signup fees, 30-day cookie tracking, and direct bank payouts.
        </p>

        @if(!empty($affiliate))
            <div class="inline-flex flex-col sm:flex-row items-center gap-3 bg-white/15 border border-white/20 backdrop-blur-md px-5 py-3 rounded-2xl text-sm font-semibold">
                <span class="text-emerald-300 flex items-center gap-1.5">
                    <i class="fas fa-check-circle"></i> Active Partner (Code: <strong class="text-amber-300 font-mono">{{ $affiliate->code }}</strong>)
                </span>
                <span class="hidden sm:inline text-white/40">|</span>
                <a href="{{ route('affiliate.dashboard') }}" class="text-white hover:text-amber-300 underline font-bold flex items-center gap-1">
                    Open Your Partner Dashboard <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
        @else
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#activateSection" class="w-full sm:w-auto px-8 py-3.5 bg-amber-400 hover:bg-amber-300 text-gray-900 font-bold rounded-xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                    <span>Activate Free Partner Link</span>
                    <i class="fas fa-arrow-down text-xs"></i>
                </a>
                <a href="#howItWorks" class="w-full sm:w-auto px-7 py-3.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/20 transition flex items-center justify-center gap-2">
                    <i class="fas fa-info-circle text-amber-400"></i>
                    <span>How It Works</span>
                </a>
            </div>
        @endif
    </div>
</section>

<!-- How It Works Section -->
<section id="howItWorks" class="py-14 md:py-20 bg-gray-50 border-b border-gray-200">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary/10 px-3 py-1 rounded-full inline-block mb-2">
                Simple 3-Step Process
            </span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">How You Earn with BookTala</h2>
            <p class="text-sm md:text-base text-gray-600 mt-2">No complicated tracking hurdles. Just share your link and watch commissions mature.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
            <div class="bg-white p-7 rounded-2xl shadow-sm border border-gray-200 text-center hover:shadow-md transition">
                <div class="w-14 h-14 bg-primary text-white rounded-2xl flex items-center justify-center text-xl font-black mx-auto mb-5 shadow-sm">
                    1
                </div>
                <h3 class="font-bold text-lg mb-2 text-gray-900">Get Your Vanity Link</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    Choose your personal referral code (e.g. <span class="font-mono font-bold text-primary">ADELEKE5</span>) and get your tracking link in 60 seconds with 30-day cookie memory.
                </p>
            </div>

            <div class="bg-white p-7 rounded-2xl shadow-sm border border-gray-200 text-center hover:shadow-md transition">
                <div class="w-14 h-14 bg-amber-500 text-white rounded-2xl flex items-center justify-center text-xl font-black mx-auto mb-5 shadow-sm">
                    2
                </div>
                <h3 class="font-bold text-lg mb-2 text-gray-900">Share with Your Network</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    Recommend luxury serviced apartments in Lekki Phase 1, Victoria Island &amp; Ikoyi with guaranteed 24/7 power, fiber internet, and hotel-grade housekeeping.
                </p>
            </div>

            <div class="bg-white p-7 rounded-2xl shadow-sm border border-gray-200 text-center hover:shadow-md transition">
                <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center text-xl font-black mx-auto mb-5 shadow-sm">
                    3
                </div>
                <h3 class="font-bold text-lg mb-2 text-gray-900">Receive {{ $rateFormatted }}% Direct Payout</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    Earn a flat {{ $rateFormatted }}% on the net room accommodation base. Request automated bank transfers to any Nigerian bank once your balance reaches ₦20,000.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Commission Potential / Calculator -->
<section class="py-14 md:py-20 bg-white">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full inline-block mb-2">
                Real Earning Scenarios
            </span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">How Much Can You Earn?</h2>
            <p class="text-sm md:text-base text-gray-600 mt-2">Here is what a flat {{ $rateFormatted }}% net accommodation commission looks like on typical BookTala bookings:</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Scenario 1 -->
            <div class="p-6 rounded-2xl bg-gradient-to-b from-gray-50 to-white border border-gray-200 text-center">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Weekend Getaway</span>
                <h3 class="text-xl font-black text-gray-900 mb-2">3 Nights in Lekki Phase 1</h3>
                <p class="text-xs text-gray-500 mb-4">₦100,000 / night · Total Net Stay: ₦300,000</p>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <span class="text-xs text-emerald-700 font-bold uppercase tracking-wider block">Your {{ $rateFormatted }}% Commission</span>
                    <span class="text-3xl font-black text-emerald-600">&#8358;{{ number_format(300000 * ($rate / 100)) }}</span>
                </div>
            </div>

            <!-- Scenario 2 -->
            <div class="p-6 pt-8 rounded-2xl bg-gradient-to-b from-amber-50/50 to-white border-2 border-amber-300 text-center relative shadow-sm">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-400 text-gray-900 text-[10px] font-black uppercase tracking-wider px-3 py-0.5 rounded-full shadow">
                    Most Popular
                </span>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Holiday / Diaspora Stay</span>
                <h3 class="text-xl font-black text-gray-900 mb-2">7 Nights in Ikoyi Luxury Suite</h3>
                <p class="text-xs text-gray-500 mb-4">₦200,000 / night · Total Net Stay: ₦1,400,000</p>
                <div class="bg-amber-100/60 border border-amber-300 rounded-xl p-4">
                    <span class="text-xs text-amber-800 font-bold uppercase tracking-wider block">Your {{ $rateFormatted }}% Commission</span>
                    <span class="text-3xl font-black text-amber-600">&#8358;{{ number_format(1400000 * ($rate / 100)) }}</span>
                </div>
            </div>

            <!-- Scenario 3 -->
            <div class="p-6 rounded-2xl bg-gradient-to-b from-gray-50 to-white border border-gray-200 text-center">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Corporate Relocation</span>
                <h3 class="text-xl font-black text-gray-900 mb-2">30-Day Executive Residence</h3>
                <p class="text-xs text-gray-500 mb-4">₦120,000 / night · Total Net Stay: ₦3,600,000</p>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <span class="text-xs text-emerald-700 font-bold uppercase tracking-wider block">Your {{ $rateFormatted }}% Commission</span>
                    <span class="text-3xl font-black text-emerald-600">&#8358;{{ number_format(3600000 * ($rate / 100)) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Activation Section -->
<section id="activateSection" class="py-16 md:py-20 bg-gray-50 border-t border-gray-200">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center max-w-2xl mx-auto mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary/10 px-3 py-1 rounded-full inline-block mb-2">
                Join Today
            </span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">Activate Your Partner Link</h2>
            <p class="text-sm md:text-base text-gray-600 mt-2">No waiting period. Instant code approval. Start promoting immediately.</p>
        </div>

        @if(!empty($affiliate))
            <!-- Already Active Partner Box -->
            <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-lg border-2 border-emerald-500 text-center">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-2">You Are Already an Active Partner!</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Your vanity referral code is <strong class="font-mono text-primary font-bold text-base">{{ $affiliate->code }}</strong>.
                </p>
                <p class="text-xs text-gray-500 mb-6">
                    Master Link: <span class="font-mono bg-gray-100 px-2 py-1 rounded select-all">{{ $affiliate->referral_url }}</span>
                </p>
                <a href="{{ route('affiliate.dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-primary/90 transition">
                    <span>Enter Partner Dashboard</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
        @elseif(auth()->check())
            <!-- Logged In User Registration Box -->
            <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-lg font-bold">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Partnering as {{ auth()->user()->name }}</h3>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <form action="{{ route('affiliate.join.store') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                            Preferred Referral Code <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 bg-gray-100 border border-r-0 border-gray-300 text-gray-500 text-xs rounded-l-xl font-mono">
                                booktala.com?ref=
                            </span>
                            <input type="text" name="code" value="{{ old('code', $defaultCode) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-r-xl font-mono font-bold uppercase text-primary focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. VIPLODGE">
                        </div>
                        @error('code')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1.5">Letters and numbers only (3–20 characters). This will be your permanent partner code.</p>
                    </div>

                    <div class="border-t border-gray-200 pt-5 mb-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">Nigerian Bank Details (Optional for now)</h4>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Bank Name (e.g. GTBank, Zenith, Access)" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                            </div>
                            <div>
                                <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="10-digit NUBAN Account Number" maxlength="10" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl font-mono focus:ring-2 focus:ring-primary focus:outline-none">
                            </div>
                            <div>
                                <input type="text" name="account_name" value="{{ old('account_name') }}" placeholder="Account Name (as registered with your bank)" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-primary/90 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle text-amber-400"></i> Activate Partner Account
                    </button>
                </form>
            </div>
        @else
            <!-- Guest Direct Registration Box -->
            <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
                <div class="text-center mb-6 pb-4 border-b border-gray-100">
                    <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider rounded-full mb-2">
                        Instant Partner Signup
                    </span>
                    <h3 class="text-2xl font-black text-gray-900">Create Your Affiliate Account</h3>
                    <p class="text-xs text-gray-500 mt-1">Join in under 60 seconds and start earning {{ (int) $defaultRate }}% commission on every shortlet stay.</p>
                </div>

                <form action="{{ route('affiliate.join.store') }}" method="POST">
                    @csrf
                    <!-- User Account Fields -->
                    <div class="space-y-4 mb-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Babatunde Adeleke" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                    Phone Number
                                </label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="08012345678" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password" required placeholder="Min 8 characters" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password_confirmation" required placeholder="Repeat password" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Vanity Affiliate Code Field -->
                    <div class="mb-5 pt-4 border-t border-gray-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Preferred Referral Code <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 bg-gray-100 border border-r-0 border-gray-300 text-gray-500 text-xs rounded-l-xl font-mono">
                                booktala.com?ref=
                            </span>
                            <input type="text" name="code" value="{{ old('code', $defaultCode) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-r-xl font-mono font-bold uppercase text-primary focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. VIPLODGE">
                        </div>
                        @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Bank Details Optional -->
                    <div class="border-t border-gray-100 pt-4 mb-6">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">Nigerian Bank Details (Optional for now)</h4>
                        <div class="grid grid-cols-1 gap-2.5">
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Bank Name (e.g. GTBank, Zenith, Access)" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                            <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="10-digit NUBAN Account Number" maxlength="10" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl font-mono focus:ring-2 focus:ring-primary focus:outline-none">
                            <input type="text" name="account_name" value="{{ old('account_name') }}" placeholder="Account Name (as registered with bank)" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-primary/90 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle text-amber-400"></i> Create Partner Account &amp; Access Dashboard
                    </button>

                    <p class="text-center text-xs text-gray-500 mt-4">
                        Already have an account? <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">Sign in here</a>
                    </p>
                </form>
            </div>
        @endif
    </div>
</section>

<!-- 4 Golden Rules / Program FAQ -->
<section class="py-14 md:py-20 bg-white">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary/10 px-3 py-1 rounded-full inline-block mb-2">
                Transparency First
            </span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">Partner Program Policies</h2>
            <p class="text-sm md:text-base text-gray-600 mt-2">Clear, fair, and anti-fraud rules for sustainable long-term partnerships.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl bg-gray-50 border border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-percentage text-amber-500 text-lg"></i>
                    <h4 class="font-bold text-gray-900">{{ (int) $defaultRate }}% Net Stay Base Calculation</h4>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Commissions are computed solely on the room accommodation rate. Refundable caution deposits and processing charges are deducted so you earn pure revenue commissions.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gray-50 border border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-hourglass-half text-blue-500 text-lg"></i>
                    <h4 class="font-bold text-gray-900">24-Hour Post Check-Out Maturity</h4>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">
                    To safeguard against cancellations or refund disputes, commissions mature 24 hours after verified guest checkout, automatically moving to your available balance.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gray-50 border border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-cookie-bite text-purple-500 text-lg"></i>
                    <h4 class="font-bold text-gray-900">30-Day Cookie Attribution</h4>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Once a traveler clicks your link, your partner code is saved in their browser for 30 days. Even if they book two weeks later, you receive the full {{ (int) $defaultRate }}% credit.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gray-50 border border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-money-bill-wave text-emerald-500 text-lg"></i>
                    <h4 class="font-bold text-gray-900">&#8358;20,000 Low Payout Threshold</h4>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Request payout anytime your balance reaches ₦20,000. Settlements are disbursed directly via instant NIP bank transfer to any registered Nigerian commercial bank.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
