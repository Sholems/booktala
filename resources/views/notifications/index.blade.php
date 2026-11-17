@extends($layout)
@section('page_title', 'Notifications')
@section('content')
<div class="max-w-3xl">
    <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-xl font-bold">Notifications</h2>
        @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route($routePrefix . '.read-all') }}">
            @csrf
            <button type="submit" class="text-sm text-primary hover:underline">Mark all as read</button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse($notifications as $notification)
        @php
            $data = $notification->data;
            $icon = match ($data['kind'] ?? 'info') {
                'escalated' => 'fa-exclamation-triangle text-orange-500',
                'assigned' => 'fa-clipboard-check text-blue-500',
                default => 'fa-bell text-gray-400',
            };
        @endphp
        <a href="{{ route($routePrefix . '.open', $notification->id) }}" class="flex gap-3 p-4 hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-blue-50/50' }}">
            <i class="fas {{ $icon }} mt-1"></i>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between gap-3">
                    <p class="{{ $notification->read_at ? 'text-gray-700' : 'font-semibold text-gray-900' }}">{{ $data['title'] ?? 'Notification' }}</p>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                @if(!empty($data['body']))<p class="text-sm text-gray-500">{{ $data['body'] }}</p>@endif
            </div>
        </a>
        @empty
        <p class="p-6 text-center text-gray-500">No notifications yet.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
@endsection
