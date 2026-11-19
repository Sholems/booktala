@if(isset($sectionsArray['cta']) && $sectionsArray['cta']->is_visible)
<section class="py-16 md:py-20 bg-slate-900 text-white relative">
    <div class="container mx-auto px-4 md:px-6 max-w-4xl text-center">
        <span class="text-xs font-bold uppercase tracking-widest text-accent mb-2 block">✦ Your Next Stay in Lagos Awaits ✦</span>
        <h2 class="text-3xl md:text-5xl font-black mb-4 tracking-tight">{{ $sectionsArray['cta']->title ?? 'Ready to Book Your Stay?' }}</h2>
        <p class="text-base md:text-xl text-gray-300 mb-8 max-w-2xl mx-auto leading-relaxed">{{ $sectionsArray['cta']->subtitle ?? 'Stay. Relax. Connect. Experience the comfort of home with 5-star hotel service.' }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ url($sectionsArray['cta']->button_link ?: route('properties.index')) }}" class="w-full sm:w-auto px-8 py-4 bg-accent hover:bg-accent/90 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition">
                {{ $sectionsArray['cta']->button_text ?? 'Browse Apartments' }}
            </a>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-sm">
                <i class="fab fa-whatsapp text-lg"></i>
                <span>WhatsApp Concierge</span>
            </a>
        </div>
    </div>
</section>
@endif
