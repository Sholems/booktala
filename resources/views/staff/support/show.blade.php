@extends('layouts.staff')
@section('page_title', 'Private Conversation - Head Office')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('staff.support.index') }}" class="p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition mr-1">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Private Thread #{{ $conversation->id }}
                        </h1>
                        <p class="text-xs text-gray-500">
                            Started {{ $conversation->created_at->format('M d, Y · g:i A') }} · Direct line to Head Office Management & HR
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    <i class="fas fa-lock text-[10px]"></i> Confidential
                </span>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <!-- Message History -->
        <div class="bg-white rounded-lg shadow-sm ring-1 ring-gray-200 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between text-xs text-gray-500">
                <span>Message History</span>
                <span>{{ $conversation->messages->count() }} message(s)</span>
            </div>

            <div class="p-4 sm:p-6 space-y-4 max-h-[600px] overflow-y-auto">
                @forelse($conversation->messages->reverse() as $msg)
                @php
                    $isMe = $msg->sender_id === auth()->id();
                @endphp
                <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                    <div class="max-w-xl rounded-2xl px-4 py-3 text-sm shadow-sm {{ $isMe ? 'bg-slate-900 text-white rounded-br-none' : 'bg-indigo-50/80 text-gray-900 rounded-bl-none ring-1 ring-indigo-200/60' }}">
                        <div class="text-[11px] font-semibold mb-1 {{ $isMe ? 'text-slate-300' : 'text-indigo-900' }}">
                            {{ $isMe ? 'You' : ($msg->sender->name ?? 'Head Office Management') }}
                        </div>
                        <div class="whitespace-pre-line leading-relaxed">
                            {{ $msg->message }}
                        </div>
                        <div class="text-[10px] mt-1 text-right {{ $isMe ? 'text-slate-400' : 'text-gray-400' }}">
                            {{ $msg->created_at->format('g:i A · M d') }}
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-center text-sm text-gray-400 py-6">No messages in this thread yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Reply Form -->
        <div class="bg-white rounded-lg p-5 shadow-sm ring-1 ring-gray-200">
            <form method="POST" action="{{ route('staff.support.reply', $conversation) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="message" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Reply to Head Office</label>
                    <textarea id="message" name="message" rows="3" maxlength="3000" required placeholder="Type your reply here..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    @error('message')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-slate-900 text-white font-medium py-2 px-5 rounded-lg hover:bg-slate-800 transition text-sm flex items-center gap-2 shadow-sm">
                        <i class="fas fa-paper-plane text-xs"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
