@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
$logo = \App\Models\Setting::get('logo');
$logoUrl = $logo ? url('media/' . ltrim($logo, '/')) : asset('images/booktala-logo.png');
$logoMarkUrl = asset('images/booktala-mark.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', trim($__env->yieldContent('page_title')) ?: 'My Dashboard') - BookTala</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <aside class="sidebar print:hidden w-1/2 lg:w-1/5 bg-[#0f172a] text-slate-300 flex flex-col h-full overflow-hidden lg:relative border-r border-slate-800 shadow-xl fixed lg:static inset-y-0 left-0 z-50">
            <!-- Branding Header -->
            <div class="p-5 lg:p-7 shrink-0 bg-[#1e293b]/50 border-b border-slate-800/50">
                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-3.5 group" aria-label="{{ $siteName }} Guest Portal">
                    <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md shadow-black/20 shrink-0 transition-transform group-hover:scale-105">
                        <img src="{{ $logo ? url('media/' . ltrim($logo, '/')) : $logoMarkUrl }}" alt="{{ $siteName }}" class="h-full w-full object-contain">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h2 class="text-xl font-black tracking-tight text-white uppercase leading-none truncate">
                            Book<span class="text-red-500">Tala</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate">
                            Guest Dashboard
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto custom-scrollbar">
                
                <!-- Overview -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Overview</p>
                    <div class="space-y-1">
                        <x-nav-link route="customer.dashboard" icon="fa-th-large">Dashboard</x-nav-link>
                    </div>
                </div>

                <!-- My Account -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">My Account</p>
                    <div class="space-y-1">
                        <x-nav-link route="customer.reservations.index" active="customer.reservations.*" icon="fa-calendar-alt">My Bookings</x-nav-link>
                        <x-nav-link route="customer.wishlist.index" active="customer.wishlist.*" icon="fa-heart">Wishlist</x-nav-link>
                        <x-nav-link route="customer.messages.index" active="customer.messages.*" icon="fa-envelope">Messages</x-nav-link>
                        <x-nav-link route="customer.profile.edit" active="customer.profile.*" icon="fa-user-circle">My Profile</x-nav-link>
                        <x-nav-link route="customer.loyalty.index" active="customer.loyalty.*" icon="fa-star">Loyalty Points</x-nav-link>
                        <x-nav-link route="customer.checkin.index" active="customer.checkin.*" icon="fa-user-check">Pre Check-in</x-nav-link>
                        <x-nav-link route="customer.room-service.index" active="customer.room-service.*" icon="fa-concierge-bell">Room Service</x-nav-link>
                        <x-nav-link route="customer.event-bookings.index" active="customer.event-bookings.*" icon="fa-calendar-day">Event Booking</x-nav-link>
                    </div>
                </div>

                <!-- Partner Program -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Partner Program</p>
                    <div class="space-y-1">
                        <a href="{{ route('affiliate.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group text-amber-400 hover:bg-slate-800/50 hover:text-amber-300">
                            <i class="fas fa-handshake w-5 text-sm"></i>
                            <span class="text-sm font-semibold">Affiliate Portal</span>
                        </a>
                    </div>
                </div>

                <!-- Shortcuts -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Links</p>
                    <div class="space-y-1">
                        <a href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group hover:bg-slate-800/50 hover:text-white">
                            <i class="fas fa-globe w-5 text-sm"></i>
                            <span class="text-sm">Back to Website</span>
                        </a>
                    </div>
                </div>

            </nav>

            <!-- Bottom Actions -->
            <div class="p-4 bg-slate-950/20 border-t border-slate-800 shrink-0 flex flex-col gap-4">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 bg-accent rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-accent/20">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Guest Account</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-red-400 hover:text-red-300 transition-colors group">
                        <i class="fas fa-sign-out-alt w-5 text-sm group-hover:-translate-x-1 transition-transform"></i>
                        <span class="text-xs font-bold uppercase tracking-widest">Logout</span>
                    </button>
                </form>
                <button class="lg:hidden w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors mt-2" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left text-xs"></i>
                    <span class="text-xs uppercase tracking-widest font-bold">Close Menu</span>
                </button>
            </div>
        </aside>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow px-4 lg:px-6 py-3 lg:py-4 flex justify-between items-center print:hidden">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden text-primary" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <a href="{{ route('customer.dashboard') }}" class="lg:hidden flex items-center shrink-0 mr-1" aria-label="{{ $siteName }}">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                    </a>
                    <h1 class="text-lg lg:text-xl font-semibold text-gray-700">@yield('page_title', 'My Dashboard')</h1>
                </div>
                <div class="text-sm text-gray-500 hidden sm:block">
                    {{ now()->format('l, F j, Y') }}
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 w-full">
                @include('partials.flash')
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
    @include('partials.pwa-scripts')
</body>
</html>