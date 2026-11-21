@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
$logo = \App\Models\Setting::get('logo');
$logoUrl = $logo ? url('media/' . ltrim($logo, '/')) : asset('images/booktala-logo.png');
$logoMarkUrl = asset('images/booktala-mark.png');
$affiliate = auth()->user()?->affiliate;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Affiliate Partner Portal') - {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/qr.js'])
    <style>
        @media (max-width: 1023px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 40; backdrop-filter: blur(2px); }
            .sidebar-overlay.open { display: block; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-[#0B2D5B] text-slate-200 flex flex-col h-full border-r border-blue-900 shadow-xl fixed lg:static inset-y-0 left-0 z-50">
            <div class="p-5 shrink-0 bg-[#07203F] border-b border-blue-900/60 flex items-center justify-between">
                <a href="{{ route('affiliate.dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md shrink-0">
                        <img src="{{ $logo ? url('media/' . ltrim($logo, '/')) : $logoMarkUrl }}" alt="{{ $siteName }}" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <h2 class="text-lg font-black tracking-tight text-white leading-none">
                            Book<span class="text-red-500">Tala</span>
                        </h2>
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest block mt-1">
                            Affiliate Partner
                        </span>
                    </div>
                </a>
                <button class="lg:hidden text-gray-400 hover:text-white" onclick="toggleSidebar()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto">
                <a href="{{ route('affiliate.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('affiliate.dashboard') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-5 text-center text-amber-400"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('affiliate.referrals') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('affiliate.referrals') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-receipt w-5 text-center text-emerald-400"></i>
                    <span>Referrals Ledger</span>
                </a>

                <a href="{{ route('affiliate.payouts') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('affiliate.payouts') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-wallet w-5 text-center text-cyan-400"></i>
                    <span>Payouts & Bank</span>
                </a>

                <a href="{{ route('affiliate.marketing') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('affiliate.marketing') ? 'bg-white/15 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullhorn w-5 text-center text-pink-400"></i>
                    <span>Marketing Toolkit</span>
                </a>

                <div class="pt-4 border-t border-blue-900/60 my-2"></div>

                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-white/5 transition">
                    <i class="fas fa-external-link-alt w-5 text-center"></i>
                    <span>Visit Public Site</span>
                </a>
            </nav>

            <!-- Bottom Profile Card -->
            <div class="p-4 bg-[#07203F] border-t border-blue-900/60 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-accent rounded-full flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        @if($affiliate)
                        <span class="inline-block text-[10px] font-mono font-bold bg-amber-400/20 text-amber-300 px-1.5 py-0.5 rounded mt-0.5">
                            {{ $affiliate->code }} (5%)
                        </span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="text-slate-400 hover:text-red-400 text-sm p-1">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Top Mobile Bar -->
            <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between lg:hidden shrink-0 shadow-sm">
                <button onclick="toggleSidebar()" class="text-gray-600 hover:text-primary p-2">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <span class="font-bold text-primary text-sm">Affiliate Partner Portal</span>
                <span class="text-xs font-mono font-bold bg-primary/10 text-primary px-2 py-1 rounded">
                    {{ $affiliate?->code }}
                </span>
            </header>

            <main class="flex-1 p-4 md:p-6 lg:p-8">
                @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
            document.querySelector('.sidebar-overlay').classList.toggle('open');
        }
    </script>
    @stack('scripts')
</body>
</html>
