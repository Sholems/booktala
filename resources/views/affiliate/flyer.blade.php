@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
$phone = config('brand.phone', '+234 814 000 8252');
$email = config('brand.email', 'concierge@booktala.com');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Flyer & Table Tent — {{ $affiliate->code }} — {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .flyer-container { box-shadow: none !important; border: none !important; margin: 0 auto !important; }
            @page {
                size: {{ $layout === 'tent' ? 'A4 landscape' : 'A4 portrait' }};
                margin: 8mm;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900 min-h-screen py-8">

    <!-- Top Action Bar (Hidden on print) -->
    <div class="no-print max-w-4xl mx-auto px-4 mb-6">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('affiliate.dashboard') }}" class="p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="font-bold text-base text-gray-900">Partner Print Marketing Material</h1>
                    <p class="text-xs text-gray-500">Partner Code: <span class="font-mono font-bold text-primary">{{ $affiliate->code }}</span></p>
                </div>
            </div>

            <!-- Layout Selector & Print Buttons -->
            <div class="flex items-center gap-2">
                <div class="bg-gray-100 p-1 rounded-xl flex items-center text-xs font-semibold">
                    <a href="{{ route('affiliate.flyer', ['layout' => 'flyer']) }}" class="px-3 py-1.5 rounded-lg transition {{ $layout === 'flyer' ? 'bg-white text-primary shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        <i class="fas fa-file-alt mr-1"></i> A4 Flyer
                    </a>
                    <a href="{{ route('affiliate.flyer', ['layout' => 'tent']) }}" class="px-3 py-1.5 rounded-lg transition {{ $layout === 'tent' ? 'bg-white text-primary shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        <i class="fas fa-tent mr-1"></i> Table Tent Fold
                    </a>
                </div>

                <a href="{{ route('affiliate.flyer.download', ['layout' => $layout]) }}" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white text-xs font-bold rounded-xl transition shadow flex items-center gap-1.5">
                    <i class="fas fa-file-pdf"></i>
                    <span>Download PDF</span>
                </a>

                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow flex items-center gap-1.5">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN PRINT PREVIEW CONTAINER -->
    <div class="max-w-4xl mx-auto px-4">

        @if($layout === 'tent')
        <!-- ========================================== -->
        <!-- TEMPLATE: FOLDABLE TABLE TENT CARD (A4 Landscape) -->
        <!-- ========================================== -->
        <div class="flyer-container bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden p-8" style="min-height: 580px;">
            <div class="grid grid-cols-2 gap-8 relative">
                <!-- Folding Divider Line -->
                <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-0 border-r-2 border-dashed border-gray-300 pointer-events-none flex flex-col items-center justify-center">
                    <span class="bg-gray-100 text-[10px] uppercase font-bold text-gray-400 px-2 py-0.5 rounded border border-gray-300 rotate-90 my-auto">
                        FOLD HERE
                    </span>
                </div>

                <!-- SIDE 1: GUEST FACING -->
                <div class="p-6 text-center flex flex-col justify-between items-center rounded-xl bg-gradient-to-b from-blue-50/40 to-white border border-gray-100">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <img src="{{ asset('images/booktala-logo.png') }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain mx-auto">
                        </div>
                        <span class="inline-block px-3 py-1 bg-[#0B2D5B] text-amber-300 text-[10px] font-black uppercase tracking-wider rounded-full mb-3">
                            Luxury Serviced Stays · Lagos
                        </span>
                        <h2 class="text-2xl font-black text-[#0B2D5B] tracking-tight leading-snug">
                            Experience 5-Star Living in Lagos
                        </h2>
                        <p class="text-xs text-gray-600 mt-1">Lekki Phase 1 · Victoria Island · Ikoyi</p>
                    </div>

                    <!-- Centered QR with Logo -->
                    <div class="my-5 p-3.5 bg-white rounded-2xl shadow-md border border-gray-200">
                        <img src="{{ $qrDataUri }}" alt="Referral QR Code" class="w-44 h-44 mx-auto block object-contain">
                        <div class="mt-2 text-center">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Scan with any phone camera</span>
                            <span class="text-xs font-mono font-bold text-[#0B2D5B]">Code: {{ $affiliate->code }}</span>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-500 space-y-0.5">
                        <p class="font-bold text-gray-800">Guaranteed 24/7 Power · High-Speed WiFi · Concierge</p>
                        <p>Book directly at <strong class="text-primary">{{ url('/') }}</strong></p>
                    </div>
                </div>

                <!-- SIDE 2: REVERSE / AMENITIES FACING -->
                <div class="p-6 text-center flex flex-col justify-between items-center rounded-xl bg-gradient-to-b from-blue-50/40 to-white border border-gray-100">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <img src="{{ asset('images/booktala-logo.png') }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain mx-auto">
                        </div>
                        <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider rounded-full mb-3">
                            100% Hospitality Guarantee
                        </span>
                        <h2 class="text-2xl font-black text-[#0B2D5B] tracking-tight leading-snug">
                            Why Stay with BookTala?
                        </h2>
                    </div>

                    <!-- 4 Amenities List -->
                    <div class="space-y-3 w-full max-w-xs text-left my-4">
                        <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-gray-100 shadow-sm">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-bold shrink-0">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="text-xs">
                                <strong class="text-gray-900 block leading-tight">Guaranteed 24/7 Power</strong>
                                <span class="text-gray-500 text-[11px]">Dual generators with automatic failover</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-gray-100 shadow-sm">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                                <i class="fas fa-wifi"></i>
                            </div>
                            <div class="text-xs">
                                <strong class="text-gray-900 block leading-tight">Superfast Fiber WiFi</strong>
                                <span class="text-gray-500 text-[11px]">Dedicated unlimited remote-work bandwidth</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-gray-100 shadow-sm">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold shrink-0">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="text-xs">
                                <strong class="text-gray-900 block leading-tight">24/7 Gated Security</strong>
                                <span class="text-gray-500 text-[11px]">Uniformed guards &amp; CCTV monitoring</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-gray-100 shadow-sm">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-sm font-bold shrink-0">
                                <i class="fas fa-broom"></i>
                            </div>
                            <div class="text-xs">
                                <strong class="text-gray-900 block leading-tight">Hotel-Grade Housekeeping</strong>
                                <span class="text-gray-500 text-[11px]">Daily cleaning &amp; crisp fresh linens</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-200 w-full text-[11px] text-gray-500">
                        <p>Questions or Concierge assistance? Call/WhatsApp: <strong class="text-gray-900 font-mono">{{ $phone }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        @else
        <!-- ========================================== -->
        <!-- TEMPLATE: A4 FULL LUXURY PROMOTIONAL FLYER -->
        <!-- ========================================== -->
        <div class="flyer-container bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden" style="max-width: 680px; margin: 0 auto;">
            <!-- Header Header Strip -->
            <div class="p-8 text-center text-white relative overflow-hidden" style="background-color: #0B2D5B; background: linear-gradient(135deg, #0B2D5B 0%, #0D3870 50%, #082042 100%);">
                <div class="inline-block bg-white p-3 rounded-2xl shadow-md mb-4">
                    <img src="{{ asset('images/booktala-logo.png') }}" alt="{{ $siteName }}" class="h-12 w-auto object-contain">
                </div>
                <span class="block text-amber-400 text-xs font-black uppercase tracking-widest mb-1">
                    ✦ Luxury Serviced Apartments · Lagos ✦
                </span>
                <h1 class="text-3xl font-black tracking-tight leading-tight mb-2">
                    Comfort of Home, Service of a 5-Star Hotel
                </h1>
                <p class="text-xs text-gray-200 max-w-md mx-auto">
                    Verified residences in Lekki Phase 1, Victoria Island &amp; Ikoyi with guaranteed 24/7 power, fiber WiFi, and 24/7 concierge.
                </p>
            </div>

            <!-- Flyer Body -->
            <div class="p-8">
                <!-- 4 Highlights Grid -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg font-bold shrink-0">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <strong class="text-xs font-bold text-gray-900 block leading-tight">Guaranteed 24/7 Power</strong>
                            <span class="text-[11px] text-gray-500">Dual heavy-duty generators</span>
                        </div>
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-bold shrink-0">
                            <i class="fas fa-wifi"></i>
                        </div>
                        <div>
                            <strong class="text-xs font-bold text-gray-900 block leading-tight">High-Speed Fiber WiFi</strong>
                            <span class="text-[11px] text-gray-500">Zoom &amp; streaming ready</span>
                        </div>
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-bold shrink-0">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <strong class="text-xs font-bold text-gray-900 block leading-tight">Gated 24/7 Security</strong>
                            <span class="text-[11px] text-gray-500">Manned access &amp; CCTV</span>
                        </div>
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg font-bold shrink-0">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div>
                            <strong class="text-xs font-bold text-gray-900 block leading-tight">Daily Housekeeping</strong>
                            <span class="text-[11px] text-gray-500">Crisp linens &amp; toiletries</span>
                        </div>
                    </div>
                </div>

                <!-- Center QR Code Box -->
                <div class="p-6 rounded-2xl bg-gradient-to-b from-blue-50/50 to-white border-2 border-primary/20 text-center mb-8">
                    <span class="inline-block px-3 py-1 bg-[#0B2D5B] text-amber-300 text-[11px] font-black uppercase tracking-wider rounded-full mb-3">
                        Scan to Browse &amp; Book Direct
                    </span>
                    <div class="w-48 h-48 bg-white p-3 rounded-2xl shadow-md border border-gray-200 mx-auto mb-3">
                        <img src="{{ $qrDataUri }}" alt="Referral QR Code" class="w-full h-full object-contain">
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Point your phone camera to scan and check instant live availability</p>
                    <p class="text-sm font-mono font-bold text-primary">Partner Tracking Code: {{ $affiliate->code }}</p>
                </div>

                <!-- Bottom Footer -->
                <div class="pt-6 border-t border-gray-200 flex items-center justify-between text-xs text-gray-600">
                    <div>
                        <strong class="text-gray-900 block">{{ $siteName }} Concierge</strong>
                        <span>{{ $phone }} · {{ $email }}</span>
                    </div>
                    <div class="text-right font-mono text-[11px]">
                        <span>{{ url('/') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

</body>
</html>
