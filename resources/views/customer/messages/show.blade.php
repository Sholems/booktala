@extends('layouts.customer')
@section('page_title', $conversation->property->name ?? 'Conversation')
@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-4 border-b flex items-center justify-between">
        <a href="{{ route('customer.messages.index') }}" class="text-primary hover:underline">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h2 class="font-semibold">{{ $conversation->property->name ?? 'General Inquiry' }}</h2>
    </div>

    <div class="p-4 h-96 overflow-y-auto space-y-4" id="messages-container">
        @forelse($conversation->messages as $message)
        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-xs lg:max-w-md">
                <div class="px-4 py-2 rounded-lg {{ $message->sender_id === auth()->id() ? 'bg-primary text-white' : 'bg-gray-100 text-gray-800' }}">
                    <p>{{ $message->message }}</p>
                </div>
                <p class="text-xs text-gray-500 mt-1 {{ $message->sender_id === auth()->id() ? 'text-right' : '' }}">
                    {{ $message->created_at->format('M d, h:i A') }}
                    @if($message->read && $message->sender_id === auth()->id())
                    <i class="fas fa-check-double ml-1"></i>
                    @endif
                </p>
            </div>
        </div>
        @empty
        <p class="text-center text-gray-500">No messages yet</p>
        @endforelse
    </div>

    <div class="p-4 border-t">
        <form method="POST" action="{{ route('customer.messages.reply', $conversation) }}">
            @csrf
            <div class="flex gap-2">
                <input type="text" name="message" class="flex-1 border rounded-lg px-4 py-2" placeholder="Type your message..." required>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                    <i class="fas fa-paper-plane"></i>
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