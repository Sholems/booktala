@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
$logo = \App\Models\Setting::get('logo');
$logoUrl = $logo ? url('media/' . ltrim($logo, '/')) : asset('images/booktala-logo.png');
$logoMarkUrl = asset('images/booktala-mark.png');

$alertService = app(\App\Services\CommunicationAlertService::class);
$staffDeskCount = auth()->check() ? $alertService->staffUnreadDeskCount(auth()->user()) : 0;
$staffFeedCount = auth()->check() ? $alertService->staffSiteFeedCount(auth()->user()) : 0;
$staffGuestMsgCount = auth()->check() ? $alertService->staffUnreadGuestMessagesCount(auth()->user()) : 0;
$staffUnreadNotifs = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
$totalStaffAlerts = $staffDeskCount + $staffFeedCount + $staffUnreadNotifs;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $totalStaffAlerts > 0 ? '(' . $totalStaffAlerts . ') ' : '' }}@yield('title', trim($__env->yieldContent('page_title')) ?: 'Staff Dashboard') - BookTala</title>
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
                <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-3.5 group" aria-label="{{ $siteName }} Staff Operations">
                    <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md shadow-black/20 shrink-0 transition-transform group-hover:scale-105">
                        <img src="{{ $logo ? url('media/' . ltrim($logo, '/')) : $logoMarkUrl }}" alt="{{ $siteName }}" class="h-full w-full object-contain">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h2 class="text-xl font-black tracking-tight text-white uppercase leading-none truncate">
                            Book<span class="text-red-500">Tala</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate">
                            Staff Operations
                        </span>
                    </div>
                </a>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto custom-scrollbar">
                
                <!-- Main -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Main</p>
                    <div class="space-y-1">
                        <x-nav-link route="staff.dashboard" icon="fa-th-large">Dashboard</x-nav-link>
                    </div>
                </div>

                <!-- Operations -->
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">Tasks & Check-in</p>
                    <div class="space-y-1">
                        <x-nav-link route="staff.tasks.index" active="staff.tasks.*" icon="fa-tasks">My Tasks</x-nav-link>
                        <x-nav-link route="staff.checkin.index" active="staff.checkin.*" icon="fa-sign-in-alt">Check-in</x-nav-link>
                        @can('reservation.create')
                        <x-nav-link route="frontdesk.reservations.create" active="frontdesk.reservations.*" icon="fa-user-plus">New Booking</x-nav-link>
                        @endcan
                        <x-nav-link route="staff.checkout.index" active="staff.checkout.*" icon="fa-sign-out-alt">Check-out</x-nav-link>
                        <x-nav-link route="staff.room-service.index" active="staff.room-service.*" icon="fa-concierge-bell">Room Service</x-nav-link>
                        <x-nav-link route="staff.support.index" active="staff.support.*" icon="fa-headset" :badge="$staffDeskCount">Head Office Desk</x-nav-link>
                        <x-nav-link route="staff.team.feed.index" active="staff.team.feed.*" icon="fa-broadcast-tower" :badge="$staffFeedCount" badgeColor="bg-blue-600">Site Feed / Radio</x-nav-link>
                        <x-nav-link route="staff.messages.index" active="staff.messages.*" icon="fa-envelope" :badge="$staffGuestMsgCount">Guest Messages</x-nav-link>
                        <x-nav-link route="staff.schedule.index" active="staff.schedule.*" icon="fa-calendar-alt">My Schedule</x-nav-link>
                        <x-nav-link route="staff.payslips.index" active="staff.payslips.*" icon="fa-money-check-alt">My Payslips</x-nav-link>
                        <x-nav-link route="staff.maintenance.index" active="staff.maintenance.*" icon="fa-tools">Maintenance</x-nav-link>
                        <x-nav-link route="staff.security.index" active="staff.security.*" icon="fa-shield-alt">Security</x-nav-link>
                        <x-nav-link route="staff.inventory.index" active="staff.inventory.*" icon="fa-boxes">Inventory</x-nav-link>
                    </div>
                </div>

                @if(\App\Support\Supervision::isSupervisor(auth()->user()))
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">My Team</p>
                    <div class="space-y-1">
                        <x-nav-link route="staff.team.index" icon="fa-users">Team Overview</x-nav-link>
                        <x-nav-link route="staff.team.roster" active="staff.team.roster*" icon="fa-calendar-week">Roster</x-nav-link>
                        <x-nav-link route="staff.team.attendance" icon="fa-user-clock">Attendance</x-nav-link>
                    </div>
                </div>
                @endif

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
                <a href="{{ route('staff.profile.edit') }}" class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-slate-800/60 transition group" title="Staff Profile">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-accent/40 shadow-md shrink-0">
                    @else
                        <div class="w-10 h-10 bg-accent rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-accent/20 shrink-0">
                            {{ auth()->user()->initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate group-hover:text-accent transition-colors">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider truncate">{{ auth()->user()->job_title ?: 'Staff Profile' }}</p>
                    </div>
                    <i class="fas fa-chevron-right text-slate-500 group-hover:text-white text-xs transition-colors"></i>
                </a>
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
                    <a href="{{ route('staff.dashboard') }}" class="lg:hidden flex items-center shrink-0 mr-1" aria-label="{{ $siteName }}">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                    </a>
                    <h1 class="text-lg lg:text-xl font-semibold text-gray-700">@yield('page_title', 'Staff Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 hidden sm:block">{{ now()->format('l, F j, Y') }}</span>
                    <a href="{{ route('staff.support.index') }}" class="relative p-2 text-gray-500 hover:text-primary transition" title="Head Office Desk">
                        <i class="fas fa-headset text-lg"></i>
                        @if($staffDeskCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center shadow-sm animate-pulse">
                            {{ $staffDeskCount > 99 ? '99+' : $staffDeskCount }}
                        </span>
                        @endif
                    </a>
                    @include('partials.notification-bell', ['portal' => 'staff'])
                    <a href="{{ route('staff.profile.edit') }}" class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition" title="My Profile">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-accent/20">
                        @else
                            <span class="w-8 h-8 bg-accent rounded-full flex items-center justify-center text-white text-xs font-bold">
                                {{ auth()->user()->initials }}
                            </span>
                        @endif
                    </a>
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
    @include('partials.pwa-scripts', ['pwaInstallPrompt' => true])
</body>
</html>
