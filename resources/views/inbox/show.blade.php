@extends($layout)
@section('page_title', 'Conversation with ' . ($conversation->user->name ?? 'Guest'))
@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-4 border-b flex items-center justify-between gap-4">
        <a href="{{ route($routePrefix . '.index') }}" class="text-primary hover:underline shrink-0">
            <i class="fas fa-arrow-left"></i> Back to messages
        </a>
        <div class="text-right min-w-0">
            <div class="flex items-center justify-end gap-2">
                <p class="font-semibold truncate">{{ $conversation->user->name ?? 'Guest' }}</p>
                @if($conversation->type === 'owner')
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">Owner</span>
                @elseif($conversation->type === 'staff')
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase bg-indigo-100 text-indigo-800 border border-indigo-200">Staff Member</span>
                @else
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase bg-blue-100 text-blue-800 border border-blue-200">Guest</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 truncate">
                {{ $conversation->user->email ?? '' }}
                @if($conversation->property) · {{ $conversation->property->title }} @endif
                @if($conversation->reservation) · {{ $conversation->reservation->booking_ref }} ({{ ucfirst(str_replace('_', ' ', $conversation->reservation->booking_status)) }}) @endif
            </p>
        </div>
    </div>

    <div class="p-4 h-96 overflow-y-auto space-y-4" id="messages-container">
        @forelse($conversation->messages as $message)
        @php $fromGuest = $message->sender_id === $conversation->user_id; @endphp
        <div class="flex {{ $fromGuest ? 'justify-start' : 'justify-end' }}">
            <div class="max-w-xs lg:max-w-md">
                <div class="px-4 py-2 rounded-lg {{ $fromGuest ? 'bg-gray-100 text-gray-800' : 'bg-accent text-white' }}">
                    <p class="whitespace-pre-line">{{ $message->message }}</p>
                </div>
                <p class="text-xs text-gray-500 mt-1 {{ $fromGuest ? '' : 'text-right' }}">
                    {{ $fromGuest ? ($conversation->user->name ?? 'Guest') : ($message->sender->name ?? 'Team') }} · {{ $message->created_at->format('M d, g:i A') }}
                </p>
            </div>
        </div>
        @empty
        <p class="text-center text-gray-500">No messages yet</p>
        @endforelse
    </div>

    <div class="p-4 border-t">
        <form method="POST" action="{{ route($routePrefix . '.reply', $conversation) }}">
            @csrf
            <div class="flex gap-2">
                <input type="text" name="message" maxlength="2000" class="flex-1 border rounded-lg px-4 py-2" placeholder="Type your reply..." required>
                <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const container = document.getElementById('messages-container');
container.scrollTop = container.scrollHeight;
</script>
@endpush
@endsection
