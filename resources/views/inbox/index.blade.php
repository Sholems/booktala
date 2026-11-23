@extends($layout)
@section('page_title', 'Messages & Communications')
@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Messages & Communications</h2>
            <p class="text-sm text-gray-500">Unread conversations are sorted first. Shared team inbox across guests, property owners, and staff.</p>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-lg text-xs self-start sm:self-auto">
            @php $currentType = $selectedType ?? 'all'; @endphp
            @foreach([
                'all' => 'All Messages',
                'guest' => 'Guests',
                'owner' => 'Owners',
                'staff' => 'Staff (HR Desk)',
            ] as $typeKey => $typeLabel)
            <a href="{{ route($routePrefix . '.index', ['type' => $typeKey]) }}"
               class="px-3 py-1.5 rounded-md font-medium transition {{ $currentType === $typeKey ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                {{ $typeLabel }}
            </a>
            @endforeach
        </div>
    </div>
    <div class="divide-y">
        @forelse($conversations as $conversation)
        <a href="{{ route($routePrefix . '.show', $conversation) }}" class="block p-4 hover:bg-gray-50 transition {{ $conversation->unread_count ? 'bg-amber-50/40' : '' }}">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 shrink-0 rounded-full flex items-center justify-center text-white font-bold {{ $conversation->type === 'staff' ? 'bg-indigo-600' : ($conversation->type === 'owner' ? 'bg-emerald-600' : 'bg-primary') }}">
                    {{ strtoupper(substr($conversation->user->name ?? ($conversation->type === 'staff' ? 'S' : 'G'), 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center gap-2 mb-0.5">
                        <div class="flex items-center gap-2 truncate">
                            <h3 class="truncate {{ $conversation->unread_count ? 'font-bold' : 'font-semibold' }} text-gray-900">
                                {{ $conversation->user->name ?? 'User' }}
                            </h3>
                            @if($conversation->type === 'staff')
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-indigo-100 text-indigo-800 border border-indigo-200 shrink-0">Staff Member</span>
                            @elseif($conversation->type === 'owner')
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Property Owner</span>
                            @else
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-blue-100 text-blue-800 border border-blue-200 shrink-0">Guest</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500 shrink-0">{{ optional($conversation->last_message_at)->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">
                        @if($conversation->type === 'staff')
                            <i class="fas fa-id-badge text-gray-400 mr-1 text-xs"></i> Internal Staff / HR Communication
                        @elseif($conversation->property)
                            <i class="fas fa-building text-gray-400 mr-1 text-xs"></i> {{ $conversation->property->title ?? $conversation->property->name }}
                        @else
                            <i class="fas fa-envelope text-gray-400 mr-1 text-xs"></i> General enquiry
                        @endif
                        @if($conversation->reservation) · {{ $conversation->reservation->booking_ref }} @endif
                    </p>
                    <p class="text-sm text-gray-600 truncate mt-0.5">{{ $conversation->messages->first()->message ?? 'No messages' }}</p>
                </div>
                @if($conversation->unread_count > 0)
                <span class="bg-red-600 text-white text-xs px-2.5 py-1 rounded-full font-bold shrink-0 animate-pulse">{{ $conversation->unread_count }} new</span>
                @endif
            </div>
        </a>
        @empty
        <div class="p-10 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
            <h3 class="text-base font-medium text-gray-700">No conversations in this view</h3>
            <p class="text-sm text-gray-400 mt-1">There are no messages matching the selected filter.</p>
        </div>
        @endforelse
    </div>
</div>

<div class="mt-4">{{ $conversations->links() }}</div>
@endsection
