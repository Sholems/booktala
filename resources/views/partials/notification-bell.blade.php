{{-- Unread in-app notifications for the signed-in staff member or admin. --}}
@php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
<a href="{{ route($portal . '.notifications.index') }}" class="relative p-2 text-gray-500 hover:text-primary" title="Notifications" aria-label="Notifications{{ $unreadNotifications ? ' (' . $unreadNotifications . ' unread)' : '' }}">
    <i class="fas fa-bell text-lg"></i>
    @if($unreadNotifications)
    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-accent text-white text-[10px] font-bold flex items-center justify-center">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
    @endif
</a>
