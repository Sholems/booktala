<!DOCTYPE html>
<html lang="en">
@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name'));
$logo = \App\Models\Setting::get('logo');
$logoUrl = $logo ? url('media/' . ltrim($logo, '/')) : asset('images/booktala-logo.png');
$favicon = \App\Models\Setting::get('favicon');
$sitePhone = \App\Models\Setting::get('phone', config('brand.phone'));
$siteEmail = \App\Models\Setting::get('email', config('brand.email'));
$siteAddress = \App\Models\Setting::get('address', config('brand.address'));
$googleProfile = \App\Models\Setting::get('google_profile', config('brand.google_profile'));
$socialLinks = array_filter([
    'facebook' => ['fa-facebook-f', 'Facebook', \App\Models\Setting::get('social_facebook', config('brand.social.facebook'))],
    'twitter' => ['fa-x-twitter', 'X (Twitter)', \App\Models\Setting::get('social_twitter', config('brand.social.twitter'))],
    'instagram' => ['fa-instagram', 'Instagram', \App\Models\Setting::get('social_instagram', config('brand.social.instagram'))],
    'linkedin' => ['fa-linkedin-in', 'LinkedIn', \App\Models\Setting::get('social_linkedin')],
    'youtube' => ['fa-youtube', 'YouTube', \App\Models\Setting::get('social_youtube')],
], fn ($link) => !empty($link[2]));
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', $siteName . ' - ' . config('brand.tagline') . '. Book fully furnished short-let apartments online.')">
    <meta name="keywords" content="@yield('meta_keywords', 'BookTala, shortlet, short let, apartment booking, serviced apartment, Lagos')">
    <title>@yield('title', $siteName . ' - Stays, Travels & Better Living')</title>
    
    <meta property="og:title" content="@yield('og_title', $siteName)">
    <meta property="og:description" content="@yield('og_description', 'Stays, Travels & Better Living')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_NG">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', $siteName)">
    <meta name="twitter:description" content="@yield('twitter_description', 'Stays, Travels & Better Living')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/og-default.jpg'))">
    @yield('extra_meta')
    
    <link rel="canonical" href="{{ url()->current() }}">
    @if($favicon)
    <link rel="icon" type="image/x-icon" href="{{ url('media/' . ltrim($favicon, '/')) }}">
    @else
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
    
    @include('partials.seo-schema-global')
    @stack('schema')
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .mobile-menu { display: none; }
        .mobile-menu.open { display: block; }
        @media (max-width: 768px) {
            .desktop-nav { display: none !important; }
            .mobile-menu { position: fixed; inset: 0; z-index: 50; background: white; padding: 1rem; overflow-y: auto; }
            .mobile-menu .nav-link { display: block; padding: 0.75rem 0; border-b border-gray-100; }
        }
    </style>
    @include('partials.pwa-head')
    @stack('styles')
</head>
<body class="bg-background text-gray-800 font-sans">
    <header class="bg-white shadow-md sticky top-0 z-50 print:hidden">
        <nav class="container mx-auto px-4 py-3 md:py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0" aria-label="{{ $siteName }} home">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 md:h-14 w-auto object-contain">
                </a>
                
                <div class="desktop-nav hidden md:flex items-center space-x-6 lg:space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">Home</a>
                    <a href="{{ route('properties.index') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">Apartments</a>
                    <a href="{{ route('pages.about') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">About</a>
                    <a href="{{ route('blog.index') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">Blog</a>
                    <a href="{{ route('pages.contact') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">Contact</a>
                    <a href="{{ route('pages.faq') }}" class="text-gray-700 hover:text-primary transition text-sm lg:text-base">FAQ</a>
                </div>
                
                <div class="flex items-center gap-2 md:space-x-4">
                    <div class="hidden md:flex items-center gap-3">
                        @auth
                            @php
                                $dashboardRoute = auth()->user()->hasAnyRole(['super_admin', 'admin']) ? 'admin.dashboard'
                                    : (auth()->user()->hasRole('staff') ? 'staff.dashboard'
                                    : (auth()->user()->hasRole('owner') ? 'owner.dashboard' : 'customer.dashboard'));
                            @endphp
                            <a href="{{ route($dashboardRoute) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-semibold">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="p-2 text-red-600 hover:text-red-800" title="Logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="px-3 py-2 text-primary hover:text-accent transition text-sm font-semibold">Login</a>
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition text-sm font-semibold shadow-sm">Register</a>
                        @endauth
                    </div>
                    <button class="md:hidden p-2 text-gray-700 hover:text-primary transition focus:outline-none" onclick="toggleMobileMenu()" aria-label="Toggle navigation">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </nav>
        
        <div id="mobile-menu" class="mobile-menu">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                <button onclick="toggleMobileMenu()" class="p-2 text-gray-600 hover:text-gray-900" aria-label="Close menu">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <a href="{{ route('home') }}" class="nav-link" onclick="toggleMobileMenu()">Home</a>
            <a href="{{ route('properties.index') }}" class="nav-link" onclick="toggleMobileMenu()">Apartments</a>
            <a href="{{ route('pages.about') }}" class="nav-link" onclick="toggleMobileMenu()">About</a>
            <a href="{{ route('blog.index') }}" class="nav-link" onclick="toggleMobileMenu()">Blog</a>
            <a href="{{ route('pages.contact') }}" class="nav-link" onclick="toggleMobileMenu()">Contact</a>
            <a href="{{ route('pages.faq') }}" class="nav-link" onclick="toggleMobileMenu()">FAQ</a>

            <div class="mt-6 pt-6 border-t border-gray-100 space-y-3">
                @auth
                    @php
                        $mDashRoute = auth()->user()->hasAnyRole(['super_admin', 'admin']) ? 'admin.dashboard'
                            : (auth()->user()->hasRole('staff') ? 'staff.dashboard'
                            : (auth()->user()->hasRole('owner') ? 'owner.dashboard' : 'customer.dashboard'));
                    @endphp
                    <a href="{{ route($mDashRoute) }}" class="block w-full text-center px-4 py-2.5 bg-primary text-white rounded-xl font-bold text-sm">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full text-center px-4 py-2.5 border border-red-200 text-red-600 rounded-xl font-bold text-sm">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block w-full text-center px-4 py-2.5 border border-primary text-primary rounded-xl font-bold text-sm">Login</a>
                    <a href="{{ route('register') }}" class="block w-full text-center px-4 py-2.5 bg-accent text-white rounded-xl font-bold text-sm shadow-sm">Register</a>
                @endauth
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('brand.phone')) }}" target="_blank" rel="noopener noreferrer" class="block w-full text-center px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-sm">
                    <i class="fab fa-whatsapp mr-1.5"></i> WhatsApp Concierge
                </a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="bg-primary text-white py-8 md:py-12 print:hidden">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10">
                <div class="lg:col-span-4">
                    <a href="{{ route('home') }}" class="inline-block bg-white rounded-lg px-3 py-2 mb-4" aria-label="{{ $siteName }} home">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-14 w-auto object-contain">
                    </a>
                    <p class="text-gray-300 text-sm leading-relaxed">{{ \App\Models\Setting::get('site_description', config('brand.description')) }}</p>
                    <p class="text-accent text-sm font-semibold mt-3 tracking-wide">{{ config('brand.tagline') }}</p>
                </div>
                <div class="lg:col-span-2">
                    <h4 class="font-bold mb-3 md:mb-4 text-sm md:text-base text-white">Explore</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('pages.about') }}" class="text-gray-300 hover:text-accent">About Us</a></li>
                        <li><a href="{{ route('properties.index') }}" class="text-gray-300 hover:text-accent">Apartments</a></li>
                        <li><a href="{{ route('blog.index') }}" class="text-gray-300 hover:text-accent">Blog &amp; News</a></li>
                        <li><a href="{{ route('pages.faq') }}" class="text-gray-300 hover:text-accent">FAQs</a></li>
                        <li><a href="{{ url('/owner-partnership-terms') }}" class="text-gray-300 hover:text-accent">List Your Property</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="text-gray-300 hover:text-accent">Contact Us</a></li>
                    </ul>
                </div>
                <div class="lg:col-span-3">
                    <h4 class="font-bold mb-3 md:mb-4 text-sm md:text-base text-white">Policies &amp; Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('pages.terms') }}" class="text-gray-300 hover:text-accent">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('pages.privacy') }}" class="text-gray-300 hover:text-accent">Privacy Policy</a></li>
                        <li><a href="{{ route('pages.cancellation') }}" class="text-gray-300 hover:text-accent">Cancellation &amp; Refunds</a></li>
                        <li><a href="{{ url('/house-rules') }}" class="text-gray-300 hover:text-accent">House Rules &amp; Guidelines</a></li>
                        <li><a href="{{ url('/security-deposit-policy') }}" class="text-gray-300 hover:text-accent">Caution Deposit Policy</a></li>
                    </ul>
                </div>
                <div class="lg:col-span-3">
                    <h4 class="font-bold mb-3 md:mb-4 text-sm md:text-base text-white">Contact &amp; Concierge</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><i class="fas fa-map-marker-alt mr-2 text-accent"></i> <span>{{ $siteAddress }}</span></li>
                        <li class="pt-1"><i class="fas fa-phone mr-2 text-accent"></i> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sitePhone) }}" class="hover:text-accent">{{ $sitePhone }}</a></li>
                        <li><i class="fas fa-envelope mr-2 text-accent"></i> <a href="mailto:{{ $siteEmail }}" class="hover:text-accent break-all">{{ $siteEmail }}</a></li>
                    </ul>
                    @if($googleProfile)
                    <a href="{{ $googleProfile }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 mt-3 text-sm text-gray-300 hover:text-accent">
                        <i class="fab fa-google"></i> Google Profile
                    </a>
                    @endif
                    <div class="mt-4 flex gap-3">
                        @foreach($socialLinks as [$icon, $label, $href])
                        <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ $label }}" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-gray-200 hover:bg-accent hover:text-white transition"><i class="fab {{ $icon }}"></i></a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved. <span class="block sm:inline mt-1 sm:mt-0">A brand of {{ config('brand.owner') }}.</span></p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Concierge Desk -->
    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\Setting::get('phone', config('brand.phone'))) }}?text=Hello%20BookTala%20Concierge,%20I%20have%20an%20inquiry%20about%20a%20stay."
       target="_blank" rel="noopener noreferrer"
       class="fixed bottom-6 right-6 z-40 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full p-3.5 shadow-xl hover:shadow-2xl flex items-center gap-2 group transition-all duration-300 hover:scale-105 print:hidden"
       aria-label="Chat on WhatsApp">
        <i class="fab fa-whatsapp text-2xl"></i>
        <span class="max-w-0 overflow-hidden whitespace-nowrap group-hover:max-w-xs transition-all duration-300 ease-in-out font-bold text-xs pr-1">
            24/7 Concierge
        </span>
    </a>

    <script>
        function toggleMobileMenu() {
            document.getElementById('mobile-menu').classList.toggle('open');
            document.body.classList.toggle('overflow-hidden');
        }
    </script>
    @stack('scripts')
    @include('partials.pwa-scripts', ['pwaInstallPrompt' => true])
</body>
</html>
