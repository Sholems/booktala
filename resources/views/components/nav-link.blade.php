{{-- A portal sidebar link. <x-nav-link route="admin.sites.index" active="admin.sites.*" icon="fa-map-marker-alt">Sites</x-nav-link> --}}
@props(['route', 'active' => null, 'icon'])
@php $isActive = request()->routeIs($active ?? $route); @endphp
<a href="{{ route($route) }}" @if($isActive) aria-current="page" @endif
   {{ $attributes->class(['flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group', 'bg-accent/10 text-accent font-semibold' => $isActive, 'hover:bg-slate-800/50 hover:text-white' => ! $isActive]) }}>
    <i class="fas {{ $icon }} w-5 text-sm" aria-hidden="true"></i>
    <span class="text-sm">{{ $slot }}</span>
</a>
