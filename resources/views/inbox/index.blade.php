@extends($layout)
@section('page_title', 'Guest Messages')
@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Guest Messages</h2>
        <p class="text-sm text-gray-500">Shared by the whole team. Unread conversations are shown first.</p>
    </div>
    <div class="divide-y">
        @forelse($conversations as $conversation)
        <a href="{{ route($routePrefix . '.show', $conversation) }}" class="block p-4 hover:bg-gray-50 {{ $conversation->unread_count ? 'bg-amber-50/40' : '' }}">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 shrink-0 bg-primary rounded-full flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($conversation->user->name ?? 'G', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center gap-2">
                        <h3 class="truncate {{ $conversation->unread_count ? 'font-bold' : 'font-semibold' }}">{{ $conversation->user->name ?? 'Guest' }}</h3>
                        <span class="text-xs text-gray-500 shrink-0">{{ optional($conversation->last_message_at)->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">
                        {{ $conversation->property->name ?? 'General enquiry' }}
                        @if($conversation->reservation) · {{ $conversation->reservation->booking_ref }} @endif
                    </p>
                    <p class="text-sm text-gray-400 truncate">{{ $conversation->messages->first()->message ?? 'No messages' }}</p>
                </div>
                @if($conversation->unread_count > 0)
                <span class="bg-accent text-white text-xs px-2 py-1 rounded-full">{{ $conversation->unread_count }} new</span>
                @endif
            </div>
        </a>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-envelope text-4xl mb-3"></i>
            <p>No messages from guests yet</p>
        </div>
        @endforelse
    </div>
</div>

<div class="mt-4">{{ $conversations->links() }}</div>
@endsection
