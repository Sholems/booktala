{{-- A portal sidebar link. <x-nav-link route="admin.sites.index" active="admin.sites.*" icon="fa-map-marker-alt" :badge="$badge">Sites</x-nav-link> --}}
@props(['route', 'active' => null, 'icon', 'badge' => null, 'badgeColor' => 'bg-red-500'])
@php $isActive = request()->routeIs($active ?? $route); @endphp
<a href="{{ route($route) }}" @if($isActive) aria-current="page" @endif
   {{ $attributes->class(['flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 group', 'bg-accent/10 text-accent font-semibold' => $isActive, 'hover:bg-slate-800/50 hover:text-white' => ! $isActive]) }}>
    <div class="flex items-center gap-3 min-w-0">
        <i class="fas {{ $icon }} w-5 text-sm" aria-hidden="true"></i>
        <span class="text-sm truncate">{{ $slot }}</span>
    </div>
    @if(!empty($badge) && (int)$badge > 0)
    <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-bold leading-none text-white {{ $badgeColor }} rounded-full shrink-0 shadow-sm">
        {{ $badge > 99 ? '99+' : $badge }}
    </span>
    @endif
</a>
