@extends('layouts.app')

@section('title', 'Book ' . $property->title . ' - BookTala')

@push('styles')
<style>
    .booking-summary { background: #f9fafb; }
    .policy-box { transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .policy-box.has-error { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15); }
</style>
@endpush

@section('content')
<section class="py-6 md:py-10 bg-gray-50/60 min-h-screen">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        <nav class="text-xs md:text-sm text-gray-600 mb-6 flex items-center gap-2">
            <a href="{{ route('home') }}" class="hover:text-primary transition">Home</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('properties.show', $property->slug) }}" class="hover:text-primary transition truncate max-w-xs md:max-w-md">{{ $property->title }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-primary font-medium">Checkout</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Checkout Steps & Policies -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                <!-- Inline Error Banner -->
                <div id="booking-error-banner" class="hidden p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3 shadow-sm">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 text-base shrink-0"></i>
                    <div class="flex-1" id="booking-error-text"></div>
                    <button type="button" onclick="document.getElementById('booking-error-banner').classList.add('hidden')" class="text-red-400 hover:text-red-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @guest
                <!-- Guest Contact Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 md:p-7">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">1</span>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Guest Information</h2>
                        </div>
                        <a href="{{ route('login') }}" class="text-xs md:text-sm text-primary font-semibold hover:underline">
                            Already have an account? Log in
                        </a>
                    </div>
                    <p class="text-xs md:text-sm text-gray-500 mb-5">Your reservation voucher, access instructions, and payment receipts will be sent to this email.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Full Name (As on valid ID) *</label>
                            <input type="text" name="guest_name" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" placeholder="e.g. Chukwudi Adeleke" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Email Address *</label>
                            <input type="email" name="guest_email" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" placeholder="name@example.com" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Phone Number (WhatsApp Active) *</label>
                            <input type="tel" name="guest_phone" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" placeholder="+234 800 000 0000" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Account Password * <span class="text-gray-400 font-normal lowercase">(8+ chars)</span></label>
                            <input type="password" name="guest_password" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" placeholder="Create or enter password" required>
                        </div>
                    </div>
                </div>
                @endguest

                <!-- Stay Details Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 md:p-7">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">@guest 2 @else 1 @endguest</span>
                        <h2 class="text-lg md:text-xl font-bold text-gray-900">Stay Dates & Occupancy</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Check-in Date *</label>
                            <input type="date" name="check_in" id="check_in_input" value="{{ request('check_in') }}" min="{{ now()->toDateString() }}" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" required>
                            <span class="text-[11px] text-gray-400 mt-1 block">Check-in from {{ $property->check_in_time ?? '2:00 PM' }}</span>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Check-out Date *</label>
                            <input type="date" name="check_out" id="check_out_input" value="{{ request('check_out') }}" min="{{ now()->addDay()->toDateString() }}" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" required>
                            <span class="text-[11px] text-gray-400 mt-1 block">Check-out by {{ $property->check_out_time ?? '11:00 AM' }}</span>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Guests *</label>
                            <select name="guests" id="guests_input" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition bg-white" required>
                                @for($i = 1; $i <= $property->max_guests; $i++)
                                <option value="{{ $i }}" {{ request('guests') == $i ? 'selected' : '' }}>
                                    {{ $i }} Guest{{ $i > 1 ? 's' : '' }} (Max {{ $property->max_guests }})
                                </option>
                                @endfor
                            </select>
                            <span class="text-[11px] text-gray-400 mt-1 block">Max occupancy enforced</span>
                        </div>
                    </div>

                    <!-- Promo Code with Apply Button -->
                    <div class="mb-5 pt-3 border-t border-gray-100">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Have a Promo or Referral Code?</label>
                        <div class="flex gap-2 max-w-md">
                            <input type="text" name="discount_code" id="discount_code_input" value="{{ request('discount_code') }}" class="flex-1 uppercase border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" placeholder="e.g. WELCOME10">
                            <button type="button" id="apply-promo-btn" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-sm font-semibold transition flex items-center gap-1.5">
                                <span>Apply</span>
                            </button>
                        </div>
                        <div id="promo-feedback" class="mt-1.5 text-xs"></div>
                    </div>
                    
                    <div class="pt-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Special Requests & Flight / Arrival Notes</label>
                        <textarea name="special_requests" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition" rows="2" placeholder="Let us know your estimated arrival time or any specific requirements (e.g. late check-in, extra towels)..."></textarea>
                    </div>
                </div>

                <!-- Check-in Identification Notice -->
                <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-2xl p-4 md:p-5 flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                        <i class="fas fa-id-card text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-indigo-950 mb-0.5">Government Photo ID Required at Check-in</h4>
                        <p class="text-xs text-indigo-800/90 leading-relaxed">
                            For security and building access compliance, the primary guest must be 18+ and present a valid photo ID (NIN, Driver's License, International Passport, or Voter's Card) matching the name on this booking upon arrival.
                        </p>
                    </div>
                </div>

                <!-- POLICY ACCEPTANCE AGREEMENT (REQUIRED) -->
                <div id="policy-box" class="policy-box bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 md:p-7">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">@guest 3 @else 2 @endguest</span>
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Policies & House Rules Agreement</h2>
                            <p class="text-xs text-gray-500">Please review and confirm your acceptance to finalize your reservation.</p>
                        </div>
                    </div>

                    <!-- Highlighted Guarantees -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5 text-xs text-gray-700">
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/70 flex items-start gap-2.5">
                            <i class="fas fa-shield-check text-emerald-600 mt-0.5 text-sm shrink-0"></i>
                            <div>
                                <span class="font-bold text-emerald-950 block">Caution Deposit</span>
                                <span class="text-emerald-800">100% refundable within 24–48 hours post-checkout inspection.</span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/70 flex items-start gap-2.5">
                            <i class="fas fa-volume-slash text-amber-600 mt-0.5 text-sm shrink-0"></i>
                            <div>
                                <span class="font-bold text-amber-950 block">No Parties Policy</span>
                                <span class="text-amber-800">Quiet hours: 10 PM – 7 AM. Loud gatherings result in immediate eviction.</span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-sky-50 border border-sky-200/70 flex items-start gap-2.5">
                            <i class="fas fa-ban-smoking text-sky-600 mt-0.5 text-sm shrink-0"></i>
                            <div>
                                <span class="font-bold text-sky-950 block">Smoke-Free Apartment</span>
                                <span class="text-sky-800">No indoor smoking. Smoking permitted in outdoor balcony only.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Required Checkbox -->
                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="policy_agreement" id="policy_agreement_input" value="1" class="mt-1 h-5 w-5 text-primary rounded border-gray-300 focus:ring-primary transition cursor-pointer">
                            <div class="text-xs md:text-sm text-gray-800 leading-normal">
                                <span class="font-bold text-gray-900">I have read, understood, and accept:</span>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <button type="button" onclick="openPolicyModal('rules')" class="text-primary font-semibold underline hover:text-accent transition">
                                        <i class="fas fa-file-contract mr-1 text-xs"></i>House Rules
                                    </button>
                                    <span class="text-gray-300">•</span>
                                    <button type="button" onclick="openPolicyModal('cancellation')" class="text-primary font-semibold underline hover:text-accent transition">
                                        <i class="fas fa-undo-alt mr-1 text-xs"></i>Cancellation & Refund Policy
                                    </button>
                                    <span class="text-gray-300">•</span>
                                    <a href="{{ route('pages.terms') }}" target="_blank" class="text-primary font-semibold underline hover:text-accent transition">
                                        <i class="fas fa-external-link-alt mr-1 text-xs"></i>Terms of Service
                                    </a>
                                </div>
                            </div>
                        </label>
                        <p id="policy-error-msg" class="hidden mt-2 text-xs text-red-600 font-semibold pl-8">
                            <i class="fas fa-exclamation-triangle mr-1"></i> You must agree to the House Rules, Cancellation Policy, and Terms of Service to proceed.
                        </p>
                    </div>
                </div>

                <!-- Payment CTA Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 md:p-7">
                    <x-turnstile action="booking_process" class="mb-5" />

                    <button type="button" id="proceed-to-payment" class="w-full bg-accent hover:bg-accent/90 text-white py-4 rounded-xl font-bold text-base md:text-lg shadow-lg shadow-accent/25 hover:shadow-xl hover:shadow-accent/30 transition transform active:scale-[0.99] flex items-center justify-center gap-3">
                        <span id="btn-spinner" class="hidden"><i class="fas fa-circle-notch fa-spin"></i></span>
                        <span id="btn-text">Proceed to Payment</span>
                        <i class="fas fa-lock text-sm opacity-80" id="btn-lock"></i>
                    </button>

                    <!-- Trust and Gateway Security Badges -->
                    <div class="mt-5 pt-5 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-500">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-alt text-emerald-600 text-base"></i>
                            <span>256-bit SSL encrypted checkout powered by <strong>Paystack</strong></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-400 text-lg">
                            <i class="fab fa-cc-mastercard hover:text-gray-600 transition" title="Mastercard"></i>
                            <i class="fab fa-cc-visa hover:text-gray-600 transition" title="Visa"></i>
                            <span class="text-[11px] font-bold border border-gray-300 rounded px-1.5 py-0.5 text-gray-600 uppercase">Verve</span>
                            <span class="text-[11px] font-bold border border-gray-300 rounded px-1.5 py-0.5 text-gray-600 uppercase">Transfer / USSD</span>
                            <i class="fab fa-apple-pay text-2xl hover:text-gray-600 transition" title="Apple Pay"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sticky Booking Summary -->
            <div class="lg:col-span-5 xl:col-span-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-1 truncate">{{ $property->title }}</h3>
                    <p class="text-gray-500 text-xs mb-4 flex items-center gap-1.5">
                        <i class="fas fa-map-marker-alt text-primary"></i>
                        <span class="truncate">{{ $property->city }}, {{ $property->state }}</span>
                    </p>
                    
                    @if($property->images->first())
                    <div class="relative overflow-hidden rounded-xl mb-4 group aspect-[16/9] bg-gray-100">
                        <img src="{{ $property->images->first()->image_path }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" alt="{{ $property->title }}">
                        <span class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-sm text-white text-[11px] font-semibold px-2.5 py-1 rounded-md">
                            {{ $property->bedrooms }} Bed • {{ $property->bathrooms }} Bath
                        </span>
                    </div>
                    @endif
                    
                    <!-- Breakdown -->
                    <div class="border-t border-gray-100 pt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>₦<span id="nightly-rate-display">{{ number_format($property->price_per_night) }}</span> × <span id="summary-nights">{{ $pricing['nights'] ?? 0 }}</span> nights</span>
                            <span class="font-medium text-gray-900">₦<span id="summary-subtotal">{{ number_format($pricing['subtotal'] ?? 0) }}</span></span>
                        </div>

                        <div id="discount-row" class="flex justify-between text-emerald-600 {{ (($pricing['discount_amount'] ?? 0) > 0) ? '' : 'hidden' }}">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-tag text-xs"></i>
                                <span>Discount</span>
                                <span id="discount-code-tag" class="text-xs bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-mono uppercase">{{ $pricing['discount_code'] ?? '' }}</span>
                            </span>
                            <span class="font-medium">-₦<span id="summary-discount">{{ number_format($pricing['discount_amount'] ?? 0) }}</span></span>
                        </div>

                        <div class="flex justify-between text-gray-600">
                            <span class="flex items-center gap-1">
                                <span>VAT (7.5%)</span>
                                <i class="fas fa-info-circle text-[11px] text-gray-400" title="Federal Inland Revenue Statutory VAT"></i>
                            </span>
                            <span class="font-medium text-gray-900">₦<span id="summary-tax">{{ number_format($pricing['tax_amount'] ?? 0) }}</span></span>
                        </div>

                        <div class="flex justify-between text-gray-600">
                            <span class="flex items-center gap-1">
                                <span>Processing fee</span>
                                <span class="text-[10px] text-gray-400 font-normal">(non-refundable)</span>
                            </span>
                            <span class="font-medium text-gray-900">₦<span id="summary-processing">{{ number_format($pricing['processing_fee'] ?? config('booking.processing_fee')) }}</span></span>
                        </div>

                        <div class="flex justify-between text-gray-600">
                            <span class="flex items-center gap-1">
                                <span>Refundable caution fee</span>
                                <span class="text-[10px] text-emerald-600 font-semibold">(100% refundable)</span>
                            </span>
                            <span class="font-medium text-gray-900">₦<span id="summary-caution">{{ number_format($pricing['caution_fee'] ?? config('booking.caution_fee')) }}</span></span>
                        </div>

                        <div class="flex justify-between py-3 border-t border-gray-200 font-bold text-base md:text-lg">
                            <span class="text-gray-900">Total</span>
                            <span class="text-primary text-xl">₦<span id="summary-total">{{ number_format($pricing['total_amount'] ?? $property->price_per_night) }}</span></span>
                        </div>
                    </div>

                    <!-- Payment Mode Options -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2.5">Select Payment Option</label>
                        
                        <div class="space-y-2">
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-primary/50 transition cursor-pointer bg-white" id="payment-mode-full-box">
                                <input type="radio" name="payment_mode" value="full" checked class="mt-1 text-primary focus:ring-primary cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-sm text-gray-900">Pay 100% Full Amount</span>
                                        <span class="text-xs font-bold text-primary">₦<span id="full-amount-display">{{ number_format($pricing['total_amount'] ?? $property->price_per_night) }}</span></span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 block mt-0.5">Instant guaranteed confirmation. No check-in balance.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-primary/50 transition cursor-pointer bg-white" id="payment-mode-partial-box">
                                <input type="radio" name="payment_mode" value="partial" class="mt-1 text-primary focus:ring-primary cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-sm text-gray-900">Pay 50% Deposit Now</span>
                                        <span class="text-xs font-bold text-emerald-700">₦<span id="partial-amount-display">{{ number_format(round(($pricing['total_amount'] ?? $property->price_per_night) * 0.5)) }}</span> now</span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 block mt-0.5">Pay half now to lock your dates; remaining 50% due before check-in.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Quick Support Contact -->
                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <i class="fab fa-whatsapp text-emerald-600 text-sm"></i>
                        <span>Need booking assistance? <a href="https://wa.me/2349116110242?text={{ urlencode('Hi BookTala, I have a question about booking ' . $property->title) }}" target="_blank" class="text-emerald-700 font-semibold hover:underline">Chat on WhatsApp</a></span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- MODAL: Policies, House Rules, & Cancellation Terms -->
<div id="policy-modal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                    <i class="fas fa-file-contract"></i>
                </span>
                <div>
                    <h3 class="font-bold text-gray-900 text-base md:text-lg" id="modal-title">BookTala Guest Policies</h3>
                    <p class="text-xs text-gray-500">Applicable to {{ $property->title }}</p>
                </div>
            </div>
            <button type="button" onclick="closePolicyModal()" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <!-- Modal Tabs -->
        <div class="flex border-b border-gray-200 text-xs md:text-sm font-semibold bg-gray-50/30">
            <button type="button" onclick="switchPolicyTab('rules')" id="tab-btn-rules" class="flex-1 py-3 px-4 border-b-2 border-primary text-primary text-center">
                House Rules
            </button>
            <button type="button" onclick="switchPolicyTab('cancellation')" id="tab-btn-cancellation" class="flex-1 py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-800 text-center">
                Cancellation & Refund
            </button>
            <button type="button" onclick="switchPolicyTab('deposit')" id="tab-btn-deposit" class="flex-1 py-3 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-800 text-center">
                Caution Deposit
            </button>
        </div>

        <!-- Modal Body Content -->
        <div class="p-6 overflow-y-auto space-y-4 text-sm text-gray-700 leading-relaxed flex-1">
            
            <!-- Tab: House Rules -->
            <div id="tab-content-rules" class="space-y-4">
                <div class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-center gap-2.5 font-medium">
                    <i class="fas fa-exclamation-circle text-amber-600 text-base shrink-0"></i>
                    <span>Compliance with building and community rules is strictly monitored for guest safety and comfort.</span>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">1. Noise & Quiet Hours</h4>
                    <p class="text-xs text-gray-600">Quiet hours are strictly observed between <strong>10:00 PM and 7:00 AM</strong> daily. No loud music, outdoor sound systems, or disturbances to residential neighbors.</p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">2. Strict No Parties / Events Rule</h4>
                    <p class="text-xs text-gray-600">Unauthorized parties, commercial video shoots, or social gatherings exceeding the registered guest count are strictly prohibited. Violations will lead to immediate booking cancellation and forfeiture of payment and caution deposit without refund.</p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">3. Smoke-Free Environment</h4>
                    <p class="text-xs text-gray-600">Smoking is prohibited inside the apartment (including cigarettes, cigars, vapes, and shisha). A ₦50,000 deep ozone odor neutralization fee applies if smoke odor is detected indoors. Outdoor balcony smoking is permitted.</p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">4. Maximum Occupancy & Visitor Policy</h4>
                    <p class="text-xs text-gray-600">Only registered guests (max: {{ $property->max_guests }}) are permitted to stay overnight. Unregistered overnight guests are not allowed past 10:00 PM without prior host approval.</p>
                </div>

                @if($property->house_rules)
                <div class="pt-3 border-t border-gray-100">
                    <h4 class="font-bold text-gray-900 mb-1">Property Specific Rules:</h4>
                    <p class="text-xs text-gray-600 whitespace-pre-line">{{ $property->house_rules }}</p>
                </div>
                @endif
            </div>

            <!-- Tab: Cancellation & Refund -->
            <div id="tab-content-cancellation" class="space-y-4 hidden">
                <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 text-xs flex items-center gap-2.5 font-medium">
                    <i class="fas fa-shield-alt text-blue-600 text-base shrink-0"></i>
                    <span>Transparent cancellation rules designed to be fair to both guests and property owners.</span>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">1. Cancellation Timelines</h4>
                    <ul class="text-xs text-gray-600 space-y-1.5 list-disc list-inside">
                        <li><strong>More than 7 days before check-in:</strong> 100% refund of nightly rate and caution deposit (processing fee is non-refundable).</li>
                        <li><strong>Between 48 hours and 7 days before check-in:</strong> 50% refund of accommodation fee + 100% caution deposit.</li>
                        <li><strong>Less than 48 hours before check-in or No-Show:</strong> Non-refundable accommodation fee. Caution deposit is refunded 100%.</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">2. Processing Fee</h4>
                    <p class="text-xs text-gray-600">The processing fee (₦{{ number_format(config('booking.processing_fee', 2000)) }}) covers payment gateway processing and admin costs and is non-refundable upon cancellation.</p>
                </div>

                @if($property->cancellation_policy)
                <div class="pt-3 border-t border-gray-100">
                    <h4 class="font-bold text-gray-900 mb-1">Host Policy:</h4>
                    <p class="text-xs text-gray-600 whitespace-pre-line">{{ $property->cancellation_policy }}</p>
                </div>
                @endif
            </div>

            <!-- Tab: Caution Deposit -->
            <div id="tab-content-deposit" class="space-y-4 hidden">
                <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 text-xs flex items-center gap-2.5 font-medium">
                    <i class="fas fa-check-circle text-emerald-600 text-base shrink-0"></i>
                    <span>Your caution deposit is 100% protected and held in escrow during your stay.</span>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">1. Refund Timeline</h4>
                    <p class="text-xs text-gray-600">Our housekeeping and inspection team completes a room check within 24 hours of your checkout. The full caution fee (₦{{ number_format(config('booking.caution_fee', 20000)) }}) is refunded directly to your original bank account/card within <strong>24 to 48 hours</strong>.</p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-1">2. What Could Incur Deductions?</h4>
                    <ul class="text-xs text-gray-600 space-y-1.5 list-disc list-inside">
                        <li>Damaged furniture, electronics, bedding, or appliances.</li>
                        <li>Indoor smoking or severe lingering odors.</li>
                        <li>Loss of unit keys, access cards, or gate fobs.</li>
                        <li>Excessive dirtiness requiring industrial deep-cleaning.</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <span class="text-xs text-gray-500">BookTala Hospitality Standards</span>
            <button type="button" onclick="closePolicyModal()" class="px-5 py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition">
                I Understand
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const propertyId = '{{ $property->id }}';
const checkAvailabilityRoute = '{{ route("availability.check") }}';
const bookingProcessRoute = '{{ route("booking.process") }}';
const csrfToken = '{{ csrf_token() }}';

// Elements
const checkInInput = document.getElementById('check_in_input');
const checkOutInput = document.getElementById('check_out_input');
const guestsInput = document.getElementById('guests_input');
const discountCodeInput = document.getElementById('discount_code_input');
const applyPromoBtn = document.getElementById('apply-promo-btn');
const promoFeedback = document.getElementById('promo-feedback');

const summaryNights = document.getElementById('summary-nights');
const summarySubtotal = document.getElementById('summary-subtotal');
const summaryDiscount = document.getElementById('summary-discount');
const discountRow = document.getElementById('discount-row');
const discountCodeTag = document.getElementById('discount-code-tag');
const summaryTax = document.getElementById('summary-tax');
const summaryProcessing = document.getElementById('summary-processing');
const summaryCaution = document.getElementById('summary-caution');
const summaryTotal = document.getElementById('summary-total');
const fullAmountDisplay = document.getElementById('full-amount-display');
const partialAmountDisplay = document.getElementById('partial-amount-display');
const nightlyRateDisplay = document.getElementById('nightly-rate-display');

const policyAgreementInput = document.getElementById('policy_agreement_input');
const policyBox = document.getElementById('policy-box');
const policyErrorMsg = document.getElementById('policy-error-msg');
const errorBanner = document.getElementById('booking-error-banner');
const errorText = document.getElementById('booking-error-text');

let isQuoteLoading = false;

// Format numbers
function formatMoney(amount) {
    return Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

// Fetch live dynamic pricing
function recalculateQuote() {
    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;
    const guests = guestsInput.value;
    const discountCode = discountCodeInput.value.trim();

    if (!checkIn || !checkOut) return;
    if (new Date(checkOut) <= new Date(checkIn)) {
        showError('Check-out date must be after check-in date.');
        return;
    }

    isQuoteLoading = true;
    const params = new URLSearchParams({
        property_id: propertyId,
        check_in: checkIn,
        check_out: checkOut,
        guests: guests,
        discount_code: discountCode
    });

    fetch(`${checkAvailabilityRoute}?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        isQuoteLoading = false;
        if (!data.available) {
            showError(data.message || 'Selected dates are not available for this apartment.');
            return;
        }

        hideError();

        // Update displays
        summaryNights.textContent = data.nights;
        summarySubtotal.textContent = formatMoney(data.subtotal);
        summaryTax.textContent = formatMoney(data.tax_amount);
        summaryProcessing.textContent = formatMoney(data.processing_fee);
        summaryCaution.textContent = formatMoney(data.caution_fee);
        summaryTotal.textContent = formatMoney(data.total_price);
        fullAmountDisplay.textContent = formatMoney(data.total_price);
        partialAmountDisplay.textContent = formatMoney(data.partial_price || (data.total_price * 0.5));

        if (data.discount_applied && data.discount_amount > 0) {
            discountRow.classList.remove('hidden');
            summaryDiscount.textContent = formatMoney(data.discount_amount);
            discountCodeTag.textContent = data.discount_code;
            promoFeedback.innerHTML = `<span class="text-emerald-600 font-semibold"><i class="fas fa-check-circle mr-1"></i> Coupon "${data.discount_code}" applied successfully!</span>`;
        } else {
            discountRow.classList.add('hidden');
            if (discountCode) {
                promoFeedback.innerHTML = `<span class="text-red-500"><i class="fas fa-times-circle mr-1"></i> Invalid, expired, or non-applicable discount code.</span>`;
            } else {
                promoFeedback.innerHTML = '';
            }
        }
    })
    .catch(() => {
        isQuoteLoading = false;
    });
}

// Event Listeners for Dynamic Recalculation
checkInInput.addEventListener('change', function() {
    if (checkInInput.value) {
        const nextDay = new Date(checkInInput.value);
        nextDay.setDate(nextDay.getDate() + 1);
        checkOutInput.min = nextDay.toISOString().split('T')[0];
        if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
            checkOutInput.value = nextDay.toISOString().split('T')[0];
        }
    }
    recalculateQuote();
});

checkOutInput.addEventListener('change', recalculateQuote);
guestsInput.addEventListener('change', recalculateQuote);

applyPromoBtn.addEventListener('click', function() {
    if (!discountCodeInput.value.trim()) {
        promoFeedback.innerHTML = '<span class="text-amber-600">Please enter a promo code first.</span>';
        return;
    }
    recalculateQuote();
});

// Modal Logic
function openPolicyModal(tab = 'rules') {
    document.getElementById('policy-modal').classList.remove('hidden');
    switchPolicyTab(tab);
}

function closePolicyModal() {
    document.getElementById('policy-modal').classList.add('hidden');
}

function switchPolicyTab(tab) {
    const tabs = ['rules', 'cancellation', 'deposit'];
    tabs.forEach(t => {
        const content = document.getElementById(`tab-content-${t}`);
        const btn = document.getElementById(`tab-btn-${t}`);
        if (t === tab) {
            content.classList.remove('hidden');
            btn.classList.add('border-primary', 'text-primary');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            content.classList.add('hidden');
            btn.classList.remove('border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });
}

// Close modal on Escape or backdrop click
document.getElementById('policy-modal').addEventListener('click', function(e) {
    if (e.target === this) closePolicyModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePolicyModal();
});

function showError(msg) {
    errorText.textContent = msg;
    errorBanner.classList.remove('hidden');
    errorBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideError() {
    errorBanner.classList.add('hidden');
}

// Process Payment Submission
document.getElementById('proceed-to-payment').addEventListener('click', function() {
    hideError();

    // Policy Acceptance Validation
    if (!policyAgreementInput.checked) {
        policyBox.classList.add('has-error');
        policyErrorMsg.classList.remove('hidden');
        policyBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        showError('Please agree to the House Rules, Cancellation Policy, and Terms of Service to continue.');
        return;
    } else {
        policyBox.classList.remove('has-error');
        policyErrorMsg.classList.add('hidden');
    }

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;

    if (!checkIn || !checkOut) {
        showError('Please select your check-in and check-out dates.');
        return;
    }

    const turnstileToken = document.querySelector('input[name="cf-turnstile-response"]')?.value;
    if (!turnstileToken) {
        showError('Please complete the security verification before continuing.');
        return;
    }

    const submitBtn = document.getElementById('proceed-to-payment');
    const btnSpinner = document.getElementById('btn-spinner');
    const btnText = document.getElementById('btn-text');
    const btnLock = document.getElementById('btn-lock');

    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
    btnSpinner.classList.remove('hidden');
    btnLock.classList.add('hidden');
    btnText.textContent = 'Securing Reservation...';

    const form = new FormData();
    form.append('property_id', propertyId);
    form.append('check_in', checkIn);
    form.append('check_out', checkOut);
    form.append('guests', guestsInput.value);
    form.append('discount_code', discountCodeInput.value.trim());
    form.append('special_requests', document.querySelector('textarea[name="special_requests"]').value || '');
    form.append('payment_mode', document.querySelector('input[name="payment_mode"]:checked').value);
    form.append('policy_agreement', '1');
    form.append('cf-turnstile-response', turnstileToken);

    @if(!auth()->check())
    const nameEl = document.querySelector('input[name="guest_name"]');
    const emailEl = document.querySelector('input[name="guest_email"]');
    const phoneEl = document.querySelector('input[name="guest_phone"]');
    const passEl = document.querySelector('input[name="guest_password"]');

    if (!nameEl.value || !emailEl.value || !phoneEl.value || !passEl.value) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
        btnSpinner.classList.add('hidden');
        btnLock.classList.remove('hidden');
        btnText.textContent = 'Proceed to Secure Payment';
        showError('Please complete all guest information fields.');
        return;
    }

    form.append('guest_name', nameEl.value);
    form.append('guest_email', emailEl.value);
    form.append('guest_phone', phoneEl.value);
    form.append('guest_password', passEl.value);
    @endif

    fetch(bookingProcessRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: form
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(({ status, body }) => {
        if (status === 200 && body.success && body.authorization_url) {
            window.location.href = body.authorization_url;
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            btnSpinner.classList.add('hidden');
            btnLock.classList.remove('hidden');
            btnText.textContent = 'Proceed to Secure Payment';

            let msg = body.message || 'Unable to process reservation. Please check your details.';
            if (body.errors) {
                const firstErr = Object.values(body.errors)[0];
                if (Array.isArray(firstErr)) msg = firstErr[0];
            }
            if (window.turnstile) window.turnstile.reset();
            showError(msg);
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
        btnSpinner.classList.add('hidden');
        btnLock.classList.remove('hidden');
        btnText.textContent = 'Proceed to Secure Payment';
        if (window.turnstile) window.turnstile.reset();
        showError('A network or server error occurred. Please check your connection and try again.');
    });
});

policyAgreementInput.addEventListener('change', function() {
    if (this.checked) {
        policyBox.classList.remove('has-error');
        policyErrorMsg.classList.add('hidden');
    }
});
</script>
@endpush
@endsection
