@extends('layouts.app')

@section('title', 'Join the BookTala Affiliate & Partner Program')

@section('content')
<section class="py-12 md:py-16 bg-[#0B2D5B] text-white text-center relative overflow-hidden">
    <div class="container mx-auto px-4 max-w-4xl relative z-10">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 text-amber-400 text-xs md:text-sm font-bold uppercase tracking-wider mb-4 border border-white/15">
            <i class="fas fa-handshake"></i> BookTala Partner Program
        </span>
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
            Earn <span class="text-amber-400">5% Commission</span> on Every Shortlet Booking
        </h1>
        <p class="text-base md:text-xl text-gray-200 max-w-2xl mx-auto leading-relaxed">
            Turn your real estate network, travel audience, and corporate connections into steady income. Zero sign-up fees. Transparent post-stay payouts.
        </p>
    </div>
</section>

<section class="py-12 md:py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- How it works 3-step grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-xl font-bold mx-auto mb-4">
                    1
                </div>
                <h3 class="font-bold text-lg mb-2 text-primary">Get Your Unique Link</h3>
                <p class="text-sm text-gray-600">Register in under 60 seconds and receive your custom referral link and vanity tracking code.</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-xl font-bold mx-auto mb-4">
                    2
                </div>
                <h3 class="font-bold text-lg mb-2 text-primary">Share with Clients</h3>
                <p class="text-sm text-gray-600">Recommend verified luxury apartments in Lekki, VI, and Ikoyi with guaranteed 24/7 power.</p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-xl font-bold mx-auto mb-4">
                    3
                </div>
                <h3 class="font-bold text-lg mb-2 text-primary">Earn 5% Direct Payout</h3>
                <p class="text-sm text-gray-600">Get 5% of the net accommodation fee paid directly into your Nigerian bank account after check-out.</p>
            </div>
        </div>

        <!-- Registration Box -->
        <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <h2 class="text-2xl font-bold text-primary mb-2 text-center">Activate Your Partner Account</h2>
            <p class="text-sm text-gray-600 mb-6 text-center">Choose your unique referral code to get started immediately.</p>

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
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">Nigerian Bank Details (Optional for now)</h3>
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
    </div>
</section>
@endsection
