<!-- VIP Club & Newsletter Signup Section -->
<section class="py-16 md:py-20 bg-slate-900 text-white relative overflow-hidden" style="background: radial-gradient(circle at top right, #1e293b 0%, var(--color-primary) 60%, #091E3A 100%);">
    <div class="container mx-auto px-4 md:px-6 max-w-5xl text-center relative z-10">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-accent/20 border border-accent/30 text-accent text-xs font-bold uppercase tracking-wider mb-3">
            <i class="fas fa-tag"></i> Exclusive VIP Member Privilege
        </span>
        <h2 class="text-2xl md:text-4xl font-black tracking-tight mb-3">
            Join the BookTala VIP Club & Get <span class="text-amber-400">10% Off</span> Your First Stay
        </h2>
        <p class="text-gray-300 text-sm md:text-base max-w-xl mx-auto mb-8 leading-relaxed">
            Subscribe to unlock instant promo code <strong class="text-white font-mono bg-white/10 px-2 py-0.5 rounded">WELCOME10</strong>, priority notifications on new luxury residences, and member-only weekend rates.
        </p>

        @if(session('newsletter_success'))
        <div class="max-w-md mx-auto mb-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-sm flex items-center justify-center gap-2">
            <i class="fas fa-check-circle text-emerald-400"></i>
            <span>{{ session('newsletter_success') }}</span>
        </div>
        @endif

        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="max-w-lg mx-auto space-y-3">
            @csrf
            <div class="flex flex-col sm:flex-row gap-2.5">
                <input type="email" name="email" required placeholder="Enter your email address" class="flex-1 px-5 py-3.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white/15 transition">
                <button type="submit" class="px-7 py-3.5 bg-accent hover:bg-accent/90 text-white font-bold text-sm rounded-xl transition shadow-lg shrink-0 flex items-center justify-center gap-2">
                    <span>Claim 10% Off</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </div>
            <x-turnstile action="newsletter_subscribe" />
        </form>
        <p class="text-[11px] text-gray-400 mt-3">We respect your privacy. No spam, ever. Unsubscribe at any time.</p>
    </div>
</section>
