<!-- Floating Availability Search Engine -->
<section class="relative z-30 -mt-10 md:-mt-14 mb-4">
    <div class="container mx-auto px-4 md:px-6 max-w-5xl">
        <form action="{{ route('properties.index') }}" method="GET" class="bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-gray-100 p-3 md:p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-center">
                <!-- Location -->
                <div class="sm:col-span-2 md:col-span-4 px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                        <i class="fas fa-map-marker-alt text-accent mr-1"></i> Destination
                    </label>
                    <select name="city" class="w-full bg-transparent text-xs md:text-sm font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                        <option value="">All Lagos Neighborhoods</option>
                        <option value="Lekki" {{ request('city') === 'Lekki' ? 'selected' : '' }}>Lekki Phase 1 & Environs</option>
                        <option value="Victoria Island" {{ request('city') === 'Victoria Island' ? 'selected' : '' }}>Victoria Island (VI)</option>
                        <option value="Ikoyi" {{ request('city') === 'Ikoyi' ? 'selected' : '' }}>Ikoyi</option>
                        <option value="Ikeja" {{ request('city') === 'Ikeja' ? 'selected' : '' }}>Ikeja GRA & Mainland</option>
                    </select>
                </div>

                <!-- Check-in & Check-out -->
                <div class="sm:col-span-2 md:col-span-4 grid grid-cols-2 gap-2">
                    <div class="px-3 py-2 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                            <i class="far fa-calendar text-primary mr-1"></i> Check-in
                        </label>
                        <input type="date" name="check_in" value="{{ request('check_in') }}" min="{{ date('Y-m-d') }}" class="w-full bg-transparent text-xs font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                    </div>
                    <div class="px-3 py-2 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                            <i class="far fa-calendar-check text-primary mr-1"></i> Check-out
                        </label>
                        <input type="date" name="check_out" value="{{ request('check_out') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full bg-transparent text-xs font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                    </div>
                </div>

                <!-- Guests -->
                <div class="sm:col-span-1 md:col-span-2 px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100/80 rounded-xl transition border border-transparent focus-within:border-primary/30">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                        <i class="fas fa-users text-primary mr-1"></i> Guests
                    </label>
                    <select name="guests" class="w-full bg-transparent text-xs md:text-sm font-semibold text-gray-900 focus:outline-none border-none p-0 cursor-pointer">
                        <option value="">Any</option>
                        <option value="1" {{ request('guests') == 1 ? 'selected' : '' }}>1 Guest</option>
                        <option value="2" {{ request('guests') == 2 ? 'selected' : '' }}>2 Guests</option>
                        <option value="3" {{ request('guests') == 3 ? 'selected' : '' }}>3 Guests</option>
                        <option value="4" {{ request('guests') == 4 ? 'selected' : '' }}>4+ Guests</option>
                        <option value="6" {{ request('guests') == 6 ? 'selected' : '' }}>6+ Guests</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="sm:col-span-1 md:col-span-2">
                    <button type="submit" class="w-full min-h-[46px] bg-accent hover:bg-accent/90 text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition">
                        <i class="fas fa-search"></i>
                        <span>Find Stays</span>
                    </button>
                </div>
            </div>
            
            <!-- Direct Booking Guarantee Micro-Trust -->
            <div class="mt-2.5 pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-500 px-1">
                <span class="flex items-center gap-1.5"><i class="fas fa-check text-emerald-500"></i> Best Rate Guaranteed</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-shield-alt text-primary"></i> 100% Verified Apartments</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-bolt text-amber-500"></i> 24/7 Power & WiFi Backed</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-headset text-accent"></i> 24/7 On-Ground Concierge</span>
            </div>
        </form>
    </div>
</section>
