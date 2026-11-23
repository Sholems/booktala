<!-- Partner with BookTala / Property Owner Section -->
<section class="py-16 md:py-24 bg-gradient-to-br from-slate-900 via-primary to-slate-900 text-white relative overflow-hidden" style="background-color: var(--color-primary); background: linear-gradient(135deg, #091E3A 0%, var(--color-primary) 60%, #091E3A 100%);">
    <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-accent/10 blur-3xl pointer-events-none"></div>
    <div class="container mx-auto px-4 md:px-6 max-w-7xl relative z-10">
        <div class="grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-5 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-xs font-semibold text-amber-300 uppercase tracking-wider mb-4">
                    <i class="fas fa-handshake"></i> Property Owner Partnership
                </span>
                <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                    Own a Luxury Apartment in Lagos? <span class="block text-accent">Maximize Your Returns.</span>
                </h2>
                <p class="mt-4 text-sm md:text-base text-gray-300 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Partner with BookTala to convert your vacant or residential property into a high-yielding serviced short-let. Enjoy 100% hands-off management, guest vetting, premium upkeep, and predictable bi-weekly rental yields.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ url('/owner-partnership-terms') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-accent hover:bg-accent/90 text-white font-bold rounded-xl shadow-lg transition">
                        <span>List Your Property</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}?text=Hello%20BookTala,%20I%20own%20a%20property%20and%20would%20like%20to%20partner%20with%20you." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-sm">
                        <i class="fab fa-whatsapp text-lg"></i>
                        <span>Speak with Acquisitions</span>
                    </a>
                </div>
            </div>

            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-accent/20 text-accent flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Up to 2x Rental Yields</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Dynamic pricing and corporate bookings substantially outperform traditional annual leases.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">100% Hands-Off Hospitality</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">We handle 24/7 guest check-in, hotel-grade housekeeping, linen laundering, and day-to-day operations.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-400/20 text-emerald-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Strict Guest Vetting</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Government ID verification, caution deposits, and house rules compliance safeguard your asset.</p>
                </div>

                <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 hover:bg-white/15 transition">
                    <div class="w-12 h-12 rounded-xl bg-blue-400/20 text-blue-400 flex items-center justify-center mb-4 text-xl">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Guaranteed On-Time Payouts</h3>
                    <p class="text-xs text-gray-300 leading-relaxed">Direct automated bank disbursements with detailed statements in your dedicated owner portal.</p>
                </div>
            </div>
        </div>

        <!-- Affiliate Partner Callout Strip -->
        <div class="mt-10 p-5 md:p-6 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center md:text-left">
                <div class="w-12 h-12 rounded-xl bg-amber-400 text-gray-900 flex items-center justify-center text-xl font-black shrink-0">
                    <i class="fas fa-handshake"></i>
                </div>
                <div>
                    @php
                        $affiliateRate = rtrim(rtrim(number_format((float) \App\Models\Setting::get('affiliate_default_commission_rate', 10), 2), '0'), '.');
                    @endphp
                    <h3 class="font-bold text-base md:text-lg text-white">Not a property owner? Earn up to {{ $affiliateRate }}% as an Affiliate Partner</h3>
                    <p class="text-xs md:text-sm text-gray-300">Refer travelers, diaspora visitors, and corporate clients to BookTala and earn up to {{ $affiliateRate }}% direct commission on every booked stay.</p>
                </div>
            </div>
            <a href="{{ route('affiliate.join') }}" class="w-full md:w-auto px-6 py-2.5 bg-amber-400 hover:bg-amber-300 text-gray-900 font-bold rounded-xl text-xs md:text-sm transition shadow shrink-0 text-center flex items-center justify-center gap-2">
                <span>Join Affiliate Program</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
</section>
