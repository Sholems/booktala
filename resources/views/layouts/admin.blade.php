@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
$propertyLabel = $isHotelMode ? 'Rooms' : 'Properties';
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
    <title>@yield('title', trim($__env->yieldContent('page_title')) ?: 'Admin Dashboard') - BookTala</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    @include('partials.pwa-head')
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <aside class="sidebar print:hidden w-1/2 lg:w-1/5 bg-[#0f172a] text-slate-300 flex flex-col h-full overflow-hidden lg:relative border-r border-slate-800 shadow-xl fixed lg:static inset-y-0 left-0 z-50">
            <!-- Branding Header -->
            <div class="p-5 lg:p-7 shrink-0 bg-[#1e293b]/50 border-b border-slate-800/50">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3.5 group" aria-label="{{ $siteName }} Admin">
                    <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md shadow-black/20 shrink-0 transition-transform group-hover:scale-105">
                        <img src="{{ $logo ? url('media/' . ltrim($logo, '/')) : $logoMarkUrl }}" alt="{{ $siteName }}" class="h-full w-full object-contain">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h2 class="text-xl font-black tracking-tight text-white uppercase leading-none truncate">
                            Book<span class="text-red-500">Tala</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate">
                            {{ $isHotelMode ? 'Hotel' : 'Shortlet' }} Management
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
                        <x-nav-link route="admin.dashboard" icon="fa-th-large">Dashboard</x-nav-link>
                    </div>
                </div>

                <!-- Bookings & Finance -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Bookings & Finance</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.reservations.index" active="admin.reservations.*" icon="fa-calendar-check">Reservations</x-nav-link>
                        @can('reservation.create')
                        <x-nav-link route="frontdesk.reservations.create" active="frontdesk.reservations.*" icon="fa-user-plus">New Front-Desk Booking</x-nav-link>
                        @endcan
                        <x-nav-link route="admin.payments.index" active="admin.payments.*" icon="fa-wallet">Payments</x-nav-link>
                        <x-nav-link route="admin.invoices.index" active="admin.invoices.*" icon="fa-file-invoice">Invoices</x-nav-link>
                        <x-nav-link route="admin.messages.index" active="admin.messages.*" icon="fa-envelope">Messages</x-nav-link>
                    </div>
                </div>

                <!-- Property Management -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Inventory</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.properties.index" active="admin.properties.*" icon="fa-building">{{ $propertyLabel }}</x-nav-link>
                        @can('site.manage')
                        <x-nav-link route="admin.sites.index" active="admin.sites.*" icon="fa-map-marker-alt">Sites</x-nav-link>
                        @endcan
                        @can('owner.manage')
                        <x-nav-link route="admin.owners.index" active="admin.owners.*" icon="fa-user-tie">Owners</x-nav-link>
                        @endcan
                        @if($isHotelMode)
                        <x-nav-link route="admin.room-types.index" active="admin.room-types.*" icon="fa-door-open">Room Types</x-nav-link>
                        <x-nav-link route="admin.hotel-amenities.index" active="admin.hotel-amenities.*" icon="fa-list-ul">Amenities</x-nav-link>
                        @endif
                        @canany(['channels.manage'])
                        <x-nav-link route="admin.channel-manager.index" active="admin.channel-manager.*" icon="fa-sync">Channels (OTA Sync)</x-nav-link>
                        @endcanany
                        @can('property.view')
                        <x-nav-link route="admin.pricing-rules.index" active="admin.pricing-rules.*" icon="fa-tags">Dynamic Pricing</x-nav-link>
                        @endcan
                    </div>
                </div>

                @canany(['finance.manage', 'payroll.manage'])
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Finance</p>
                    <div class="space-y-1">
                        @can('finance.manage')
                        <x-nav-link route="admin.expenses.index" active="admin.expenses.*" icon="fa-receipt">Expenses</x-nav-link>
                        <x-nav-link route="admin.agreements.index" active="admin.agreements.*" icon="fa-file-contract">Agreements</x-nav-link>
                        <x-nav-link route="admin.statements.index" active="admin.statements.*" icon="fa-file-invoice-dollar">Owner Statements</x-nav-link>
                        <x-nav-link route="admin.finance.ledger.index" active="admin.finance.ledger.*" icon="fa-book-open">General Ledger</x-nav-link>
                        <x-nav-link route="admin.finance.tax.index" active="admin.finance.tax.*" icon="fa-landmark">Tax & WHT Reports</x-nav-link>
                        <x-nav-link route="admin.affiliates.index" active="admin.affiliates.*" icon="fa-handshake">Affiliate Partners</x-nav-link>
                        @endcan
                        @can('payroll.manage')
                        <x-nav-link route="admin.payroll.index" active="admin.payroll.*" icon="fa-money-check-alt">Payroll</x-nav-link>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Guest Experience -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Guest Experience</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.customers.index" active="admin.customers.*" icon="fa-users">Customers</x-nav-link>
                        <x-nav-link route="admin.reviews.index" active="admin.reviews.*" icon="fa-star">Reviews</x-nav-link>
                    </div>
                </div>

                <!-- Operations -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Operations</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.tasks.index" active="admin.tasks.*" icon="fa-tasks">Tasks</x-nav-link>
                        <x-nav-link route="admin.inspections.index" active="admin.inspections.*" icon="fa-clipboard-check">Room Inspections</x-nav-link>
                        <x-nav-link route="admin.room-service.index" active="admin.room-service.*" icon="fa-concierge-bell">Room Service</x-nav-link>
                        <x-nav-link route="admin.maintenance.index" active="admin.maintenance.*" icon="fa-tools">Maintenance</x-nav-link>
                        <x-nav-link route="admin.facilities.index" active="admin.facilities.*" icon="fa-cogs">Facilities & Power</x-nav-link>
                        <x-nav-link route="admin.staff.index" active="admin.staff.*" icon="fa-user-tie">Staff</x-nav-link>
                        @can('staff.manage')
                        <x-nav-link route="admin.positions.index" active="admin.positions.*" icon="fa-id-badge">Positions</x-nav-link>
                        @endcan
                        <x-nav-link route="admin.staff-schedules.index" active="admin.staff-schedules.*" icon="fa-calendar-alt">Schedules</x-nav-link>
                        <x-nav-link route="admin.attendance.index" active="admin.attendance.*" icon="fa-user-clock">Attendance</x-nav-link>
                        @can('site.manage')
                        <x-nav-link route="admin.site-feed.index" active="admin.site-feed.*" icon="fa-broadcast-tower">Site Feeds</x-nav-link>
                        <x-nav-link route="admin.security.index" active="admin.security.*" icon="fa-shield-alt">Security</x-nav-link>
                        <x-nav-link route="admin.inventory.index" active="admin.inventory.*" icon="fa-boxes">Inventory</x-nav-link>
                        @endcan
                    </div>
                </div>

                <!-- Insights -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Analytics</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.reports.index" active="admin.reports.*" icon="fa-chart-pie">Reports</x-nav-link>
                        <x-nav-link route="admin.reports.sites" active="admin.reports.sites*" icon="fa-city">Site Report</x-nav-link>
                        <a href="{{ route('admin.ical.url') }}" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group hover:bg-slate-800/50 hover:text-white">
                            <i class="fas fa-calendar-week w-5 text-sm"></i>
                            <span class="text-sm">Export to Calendar</span>
                        </a>
                        <x-nav-link route="admin.event-bookings.index" active="admin.event-bookings.*" icon="fa-calendar-day">Event Bookings</x-nav-link>
                        <x-nav-link route="admin.event-spaces.index" active="admin.event-spaces.*" icon="fa-vector-square">Event Spaces</x-nav-link>
                    </div>
                </div>

                <!-- Content & Marketing -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Marketing & Content</p>
                    <div class="space-y-1">
                        <x-nav-link route="admin.marketing.campaigns.index" active="admin.marketing.campaigns.*" icon="fa-paper-plane">Email Campaigns</x-nav-link>
                        <x-nav-link route="admin.marketing.subscribers.index" active="admin.marketing.subscribers.*" icon="fa-envelope-open-text">Subscribers</x-nav-link>
                        <x-nav-link route="admin.blogs.index" active="admin.blogs.*" icon="fa-pen-nib">Blog</x-nav-link>
                        <x-nav-link route="admin.pages.index" active="admin.pages.*" icon="fa-file-alt">Pages</x-nav-link>
                        <x-nav-link route="admin.homepage-sections.index" active="admin.homepage-sections.*" icon="fa-th-large">Homepage Settings</x-nav-link>
                    </div>
                </div>

                <!-- Configuration -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Configuration</p>
                    <div class="space-y-1">
                        @can('settings.manage')
                        <x-nav-link route="admin.settings.index" active="admin.settings.*" icon="fa-cogs">Global Settings</x-nav-link>
                        @endcan
                    </div>
                </div>

            </nav>

            <!-- Bottom User & Actions -->
            <div class="p-4 bg-slate-950/20 border-t border-slate-800 shrink-0 flex flex-col gap-3">
                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-slate-800/60 transition group" title="My Profile">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary/40 shrink-0">
                    @else
                        <div class="w-9 h-9 bg-accent rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ auth()->user()->initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate group-hover:text-accent transition-colors">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->job_title ?: 'Administrator' }}</p>
                    </div>
                    <i class="fas fa-user-cog text-slate-500 group-hover:text-white text-xs transition-colors"></i>
                </a>

                <button class="lg:hidden w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" onclick="toggleSidebar()">
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
                    <a href="{{ route('admin.dashboard') }}" class="lg:hidden flex items-center shrink-0 mr-1" aria-label="{{ $siteName }}">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                    </a>
                    <h1 class="text-lg lg:text-xl font-bold truncate">@yield('page_title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-2 lg:space-x-4">
                    @include('partials.notification-bell', ['portal' => 'admin'])
                    <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-gray-100 transition group" title="My Profile">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary/20">
                        @else
                            <span class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-primary/20">
                                {{ auth()->user()->initials }}
                            </span>
                        @endif
                        <div class="hidden md:flex flex-col text-left">
                            <span class="text-xs font-bold text-gray-800 leading-tight group-hover:text-primary transition-colors">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-gray-500 leading-tight">{{ auth()->user()->job_title ?: 'Admin' }}</span>
                        </div>
                    </a>
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
