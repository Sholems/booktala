@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
$propertyLabel = $isHotelMode ? 'Rooms' : 'Properties';
$siteName = \App\Models\Setting::get('site_name', config('brand.name'));
$logo = \App\Models\Setting::get('logo');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - BookTala</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    <style>
        @media (max-width: 1024px) {
            .sidebar { position: fixed; z-index: 50; transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
            .sidebar-overlay.open { display: block; }
        }
        .nav-item { @apply block py-2 px-4 rounded hover:bg-gray-700 transition flex items-center gap-3; }
        .nav-item.active { @apply bg-gray-700; }
        .nav-section-title { @apply px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-4; }
        .nav-section-title:first-child { @apply mt-0; }
        .nav-divider { @apply border-t border-gray-700 my-2; }
        
        /* Mobile-first responsive tables */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-responsive table { width: 100%; }
        
        @media (max-width: 768px) {
            .mobile-card-table { display: block; }
            .mobile-card-table thead { display: none; }
            .mobile-card-table tbody { display: flex; flex-direction: column; gap: 1rem; }
            .mobile-card-table tr { display: flex; flex-direction: column; background: white; border-radius: 0.5rem; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .mobile-card-table td { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
            .mobile-card-table td:last-child { border-bottom: none; }
            .mobile-card-table td::before { content: attr(data-label); font-weight: 600; color: #6b7280; margin-right: 1rem; min-width: 100px; }
        }
    </style>
    @include('partials.pwa-head')
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <aside class="sidebar w-1/2 lg:w-1/5 bg-[#0f172a] text-slate-300 flex flex-col h-full overflow-hidden lg:relative border-r border-slate-800 shadow-xl fixed lg:static inset-y-0 left-0 z-50">
            <!-- Branding Header -->
            <div class="p-5 lg:p-7 shrink-0 bg-[#1e293b]/50 border-b border-slate-800/50">
                <div class="flex items-center gap-4">
                    @if($logo)
                    <img src="{{ url('media/' . ltrim($logo, '/')) }}" alt="Logo" class="h-12 w-auto object-contain">
                    @else
                    <div class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-lg shadow-accent/20 transition-transform hover:scale-105 active:scale-95 cursor-default">
                        <i class="fas fa-building text-white text-xl"></i>
                    </div>
                    @endif
                    <div class="flex flex-col">
                        <h2 class="text-xl font-black tracking-tight text-white uppercase leading-none">
                            {{ $siteName }}
                        </h2>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">
                            {{ $isHotelMode ? 'Hotel' : 'Shortlet' }} Management
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto custom-scrollbar">
                
                <!-- Overview -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Overview</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-th-large w-5 text-sm"></i>
                            <span class="text-sm">Dashboard</span>
                        </a>
                    </div>
                </div>

                <!-- Bookings & Finance -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Bookings & Finance</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.reservations.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.reservations.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-calendar-check w-5 text-sm"></i>
                            <span class="text-sm">Reservations</span>
                        </a>
                        @can('reservation.create')
                        <a href="{{ route('frontdesk.reservations.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('frontdesk.reservations.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-user-plus w-5 text-sm"></i>
                            <span class="text-sm">New Front-Desk Booking</span>
                        </a>
                        @endcan
                        <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.payments.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-wallet w-5 text-sm"></i>
                            <span class="text-sm">Payments</span>
                        </a>
                        <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.invoices.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-file-invoice w-5 text-sm"></i>
                            <span class="text-sm">Invoices</span>
                        </a>
                        <a href="{{ route('admin.messages.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.messages.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-envelope w-5 text-sm"></i>
                            <span class="text-sm">Messages</span>
                        </a>
                    </div>
                </div>

                <!-- Property Management -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Inventory</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.properties.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.properties.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-building w-5 text-sm"></i>
                            <span class="text-sm">{{ $propertyLabel }}</span>
                        </a>
                        @can('site.manage')
                        <a href="{{ route('admin.sites.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.sites.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-map-marker-alt w-5 text-sm"></i>
                            <span class="text-sm">Sites</span>
                        </a>
                        @endcan
                        @can('owner.manage')
                        <a href="{{ route('admin.owners.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.owners.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-user-tie w-5 text-sm"></i>
                            <span class="text-sm">Owners</span>
                        </a>
                        @endcan
                        @if($isHotelMode)
                        <a href="{{ route('admin.room-types.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.room-types.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-door-open w-5 text-sm"></i>
                            <span class="text-sm">Room Types</span>
                        </a>
                        <a href="{{ route('admin.hotel-amenities.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.hotel-amenities.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-list-ul w-5 text-sm"></i>
                            <span class="text-sm">Amenities</span>
                        </a>
                        @can('settings.manage')
                        <a href="{{ route('admin.channel-manager.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.channel-manager.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-sync w-5 text-sm"></i>
                            <span class="text-sm">Channels</span>
                        </a>
                        @endcan
                        @endif
                    </div>
                </div>

                @canany(['finance.manage', 'payroll.manage'])
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Finance</p>
                    <div class="space-y-1">
                        @can('finance.manage')
                        <a href="{{ route('admin.expenses.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.expenses.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-receipt w-5 text-sm"></i>
                            <span class="text-sm">Expenses</span>
                        </a>
                        <a href="{{ route('admin.agreements.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.agreements.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-file-contract w-5 text-sm"></i>
                            <span class="text-sm">Agreements</span>
                        </a>
                        <a href="{{ route('admin.statements.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.statements.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-file-invoice-dollar w-5 text-sm"></i>
                            <span class="text-sm">Owner Statements</span>
                        </a>
                        @endcan
                        @can('payroll.manage')
                        <a href="{{ route('admin.payroll.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.payroll.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-money-check-alt w-5 text-sm"></i>
                            <span class="text-sm">Payroll</span>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Guest Experience -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Guest Experience</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.customers.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-users w-5 text-sm"></i>
                            <span class="text-sm">Customers</span>
                        </a>
                        <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.reviews.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-star w-5 text-sm"></i>
                            <span class="text-sm">Reviews</span>
                        </a>
                    </div>
                </div>

                <!-- Operations -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Operations</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.tasks.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.tasks.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-tasks w-5 text-sm"></i>
                            <span class="text-sm">Tasks</span>
                        </a>
                        <a href="{{ route('admin.room-service.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.room-service.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-concierge-bell w-5 text-sm"></i>
                            <span class="text-sm">Room Service</span>
                        </a>
                        <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.maintenance.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-tools w-5 text-sm"></i>
                            <span class="text-sm">Maintenance</span>
                        </a>
                        <a href="{{ route('admin.staff.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.staff.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-user-tie w-5 text-sm"></i>
                            <span class="text-sm">Staff</span>
                        </a>
                        @can('staff.manage')
                        <a href="{{ route('admin.positions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.positions.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-id-badge w-5 text-sm"></i>
                            <span class="text-sm">Positions</span>
                        </a>
                        @endcan
                        <a href="{{ route('admin.staff-schedules.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.staff-schedules.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-calendar-alt w-5 text-sm"></i>
                            <span class="text-sm">Schedules</span>
                        </a>
                        <a href="{{ route('admin.attendance.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.attendance.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-user-clock w-5 text-sm"></i>
                            <span class="text-sm">Attendance</span>
                        </a>
                        @can('site.manage')
                        <a href="{{ route('admin.security.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.security.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-shield-alt w-5 text-sm"></i>
                            <span class="text-sm">Security</span>
                        </a>
                        <a href="{{ route('admin.inventory.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.inventory.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-boxes w-5 text-sm"></i>
                            <span class="text-sm">Inventory</span>
                        </a>
                        @endcan
                    </div>
                </div>

                <!-- Insights -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Analytics</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.reports.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-chart-pie w-5 text-sm"></i>
                            <span class="text-sm">Reports</span>
                        </a>
                        <a href="{{ route('admin.reports.sites') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.reports.sites*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-city w-5 text-sm"></i>
                            <span class="text-sm">Site Report</span>
                        </a>
                        <a href="{{ route('admin.ical.url') }}" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group hover:bg-slate-800/50 hover:text-white">
                            <i class="fas fa-calendar-week w-5 text-sm"></i>
                            <span class="text-sm">Export to Calendar</span>
                        </a>
                        <a href="{{ route('admin.event-bookings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.event-bookings.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-calendar-day w-5 text-sm"></i>
                            <span class="text-sm">Event Bookings</span>
                        </a>
                        <a href="{{ route('admin.event-spaces.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.event-spaces.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-vector-square w-5 text-sm"></i>
                            <span class="text-sm">Event Spaces</span>
                        </a>
                    </div>
                </div>

                <!-- Content & Marketing -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Content</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.blogs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.blogs.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-pen-nib w-5 text-sm"></i>
                            <span class="text-sm">Blog</span>
                        </a>
                        <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.pages.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-file-alt w-5 text-sm"></i>
                            <span class="text-sm">Pages</span>
                        </a>
                        <a href="{{ route('admin.homepage-sections.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.homepage-sections.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-th-large w-5 text-sm"></i>
                            <span class="text-sm">Homepage Settings</span>
                        </a>
                    </div>
                </div>

                <!-- Configuration -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Configuration</p>
                    <div class="space-y-1">
                        @can('settings.manage')
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.settings.*') ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                            <i class="fas fa-cogs w-5 text-sm"></i>
                            <span class="text-sm">Global Settings</span>
                        </a>
                        @endcan
                    </div>
                </div>

            </nav>

            <!-- Bottom Actions -->
            <div class="p-4 bg-slate-950/20 border-t border-slate-800 shrink-0">
                <button class="lg:hidden w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left text-xs"></i>
                    <span class="text-xs uppercase tracking-widest font-bold">Close Menu</span>
                </button>
            </div>
        </aside>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow px-4 lg:px-6 py-3 lg:py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden text-primary" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg lg:text-xl font-bold truncate">@yield('page_title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-2 lg:space-x-4">
                    @include('partials.notification-bell', ['portal' => 'admin'])
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </span>
                        <span class="text-sm text-gray-600 hidden md:inline">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 p-2" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
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
        
        document.addEventListener('DOMContentLoaded', function() {
            // Skip editors a page has already set up itself (Quill adds .ql-container).
            document.querySelectorAll('.rich-editor:not(.ql-container)').forEach(function(el) {
                new Quill(el, {
                    theme: 'snow',
                    height: 300,
                    placeholder: 'Enter content here...',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'color': [] }, { 'background': [] }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    }
                });
            });
        });
    </script>
    @stack('scripts')
    @include('partials.pwa-scripts')
</body>
</html>
