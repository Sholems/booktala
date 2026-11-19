<!-- In-Stay Luxury Concierge & Guest Experiences Showcase -->
<section class="py-16 md:py-24 bg-white border-y border-gray-100">
    <div class="container mx-auto px-4 md:px-6 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12 md:mb-16">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-accent mb-1.5 block">Tailored Hospitality</span>
                <h2 class="text-2xl md:text-4xl font-extrabold text-primary tracking-tight">Beyond Accommodation: In-Stay Concierge</h2>
                <p class="text-gray-600 text-sm md:text-base mt-1.5 max-w-2xl">From the moment you touch down in Lagos to your checkout, our on-ground hospitality team curates every detail of your stay.</p>
            </div>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}?text=Hello%20BookTala,%20I%20would%20like%20to%20inquire%20about%20your%20in-stay%20concierge%20services." target="_blank" rel="noopener noreferrer" class="hidden md:inline-flex items-center gap-2 text-sm font-bold text-primary hover:text-accent transition">
                <span>Request Custom Service</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Airport Pickup -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-5 text-2xl group-hover:bg-primary group-hover:text-white transition duration-300">
                        <i class="fas fa-plane-arrival"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Airport Chauffeur Transfer</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Flight-tracked meet & greet at MMIA (LOS) International & Domestic terminals with direct transfer to your apartment.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Flight Tracking Included</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Private Chef -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mb-5 text-2xl group-hover:bg-amber-500 group-hover:text-white transition duration-300">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Private Chef On-Demand</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Fresh breakfast, authentic local delicacies, or private dinners prepared directly in your equipped apartment kitchen.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Custom Menus</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Turn-Down Housekeeping & Laundry -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center mb-5 text-2xl group-hover:bg-blue-600 group-hover:text-white transition duration-300">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Turn-Down & Laundry</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Scheduled linen refreshes, daily housekeeping, and same-day express dry cleaning delivered back to your door.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Same-Day Express</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>

            <!-- Car Rental & Security Escort -->
            <div class="group bg-slate-50/70 hover:bg-white rounded-2xl p-7 border border-slate-100 hover:border-gray-200 shadow-xs hover:shadow-lg transition duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/10 text-accent flex items-center justify-center mb-5 text-2xl group-hover:bg-accent group-hover:text-white transition duration-300">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Chauffeured Car Hire</h3>
                    <p class="text-gray-600 text-xs leading-relaxed">Executive SUVs and sedans with vetted, security-trained drivers for seamless business mobility across Lagos.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100 text-[11px] font-semibold text-primary flex items-center justify-between">
                    <span>Vetted Chauffeurs</span>
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
            </div>
        </div>
    </div>
</section>
