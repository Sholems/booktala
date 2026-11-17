@extends('layouts.customer')
@section('page_title', 'Messages')
@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Messages</h2>
    </div>
    <div class="divide-y">
        @forelse($conversations as $conversation)
        <a href="{{ route('customer.messages.show', $conversation) }}" class="block p-4 hover:bg-gray-50">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($conversation->user->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold truncate">{{ $conversation->property->name ?? 'General Inquiry' }}</h3>
                        <span class="text-xs text-gray-500">{{ $conversation->last_message_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">
                        {{ $conversation->messages->first()->message ?? 'No messages yet' }}
                    </p>
                </div>
                @php
                $unread = $conversation->messages()->where('receiver_id', auth()->id())->where('read', false)->count();
                @endphp
                @if($unread > 0)
                <span class="bg-accent text-white text-xs px-2 py-1 rounded-full">{{ $unread }}</span>
                @endif
            </div>
        </a>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-envelope text-4xl mb-3"></i>
            <p>No conversations yet</p>
            <a href="{{ route('properties.index') }}" class="text-primary hover:underline mt-2 inline-block">Browse properties</a>
        </div>
        @endforelse
    </div>
</div>
@endsection