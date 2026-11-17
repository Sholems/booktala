<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Owner Portal') - BookTala</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 1024px) {
            .sidebar { position: fixed; z-index: 50; transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
            .sidebar-overlay.open { display: block; }
        }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
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
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar w-3/4 sm:w-1/2 lg:w-1/5 bg-[#0f172a] text-slate-300 flex flex-col h-full overflow-hidden lg:relative border-r border-slate-800 shadow-xl fixed lg:static inset-y-0 left-0 z-50">
            <div class="p-5 lg:p-7 shrink-0 bg-[#1e293b]/50 border-b border-slate-800/50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-lg shadow-accent/20">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-xl font-black tracking-tight text-white uppercase leading-none">Book<span class="text-accent">Tala</span></h2>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Owner Portal</span>
                    </div>
                </div>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                @foreach([['owner.dashboard', 'owner.dashboard', 'fa-chart-pie', 'Overview'], ['owner.statements.index', 'owner.statements.*', 'fa-file-invoice-dollar', 'Statements']] as [$route, $pattern, $icon, $label])
                <a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 {{ request()->routeIs($pattern) ? 'bg-accent/10 text-accent font-semibold' : 'hover:bg-slate-800/50 hover:text-white' }}">
                    <i class="fas {{ $icon }} w-5 text-sm"></i>
                    <span class="text-sm">{{ $label }}</span>
                </a>
                @endforeach
            </nav>
            <div class="p-4 bg-slate-950/20 border-t border-slate-800 shrink-0 flex flex-col gap-4">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 bg-accent rounded-full flex items-center justify-center text-white text-sm font-bold">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Property Owner</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-red-400 hover:text-red-300">
                        <i class="fas fa-sign-out-alt w-5 text-sm"></i>
                        <span class="text-xs font-bold uppercase tracking-widest">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow px-4 lg:px-6 py-3 lg:py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden text-primary" onclick="toggleSidebar()" aria-label="Menu"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-lg lg:text-xl font-semibold text-gray-700">@yield('page_title', 'Owner Portal')</h1>
                </div>
                <div class="text-sm text-gray-500 hidden sm:block">{{ now()->format('l, F j, Y') }}</div>
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
