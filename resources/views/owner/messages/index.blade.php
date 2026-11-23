@extends('layouts.owner')
@section('page_title', 'Asset Management Concierge')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-100 text-emerald-800">
                        <i class="fas fa-comments text-lg"></i>
                    </span>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Asset Concierge & Inquiries</h1>
                        <p class="text-sm text-gray-500">Direct, private communication with BookTala Portfolio & Asset Managers</p>
                    </div>
                </div>
            </div>

            <a href="{{ route('owner.dashboard') }}" class="inline-flex items-center px-3.5 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 self-start md:self-auto">
                <i class="fas fa-arrow-left mr-2 text-gray-400"></i> Back to Overview
            </a>
        </div>

        @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Conversations List -->
            <div class="lg:col-span-2 space-y-3">
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Your Inquiry Threads</h2>
                        <span class="text-xs text-gray-500">{{ $conversations->total() }} thread(s)</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse($conversations as $conv)
                        @php
                            $lastMsg = $conv->messages->first();
                        @endphp
                        <a href="{{ route('owner.messages.show', $conv) }}" class="block p-4 hover:bg-slate-50 transition {{ $conv->unread_count > 0 ? 'bg-amber-50/40' : '' }}">
                            <div class="flex items-start justify-between gap-3 mb-1.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900">
                                        @if($conv->property)
                                            <i class="fas fa-building text-xs text-gray-400 mr-1"></i> {{ $conv->property->title }}
                                        @else
                                            <i class="fas fa-briefcase text-xs text-gray-400 mr-1"></i> General Asset Inquiries
                                        @endif
                                    </span>

                                    @if($conv->unread_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                                        {{ $conv->unread_count }} new
                                    </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400 shrink-0">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : $conv->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 line-clamp-2">
                                @if($lastMsg)
                                    <span class="font-medium text-gray-700">{{ $lastMsg->sender_id === auth()->id() ? 'You: ' : 'BookTala: ' }}</span>
                                    {{ $lastMsg->message }}
                                @else
                                    <span class="italic text-gray-400">No messages in this thread yet.</span>
                                @endif
                            </p>
                        </a>
                        @empty
                        <div class="p-10 text-center">
                            <i class="fas fa-comment-dots text-gray-300 text-4xl mb-3"></i>
                            <h3 class="text-sm font-semibold text-gray-700">No conversation threads yet</h3>
                            <p class="text-xs text-gray-500 mt-1">Have a question regarding your statements, maintenance repairs, or unit performance? Send a message to the right.</p>
                        </div>
                        @endforelse
                    </div>

                    @if($conversations->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $conversations->links() }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Start New Inquiry Form -->
            <div>
                <div class="bg-white rounded-lg p-5 shadow-sm ring-1 ring-gray-200">
                    <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <i class="fas fa-paper-plane text-emerald-600"></i> New Concierge Inquiry
                    </h2>
                    <p class="text-xs text-gray-500 mb-4">Inquire about monthly payouts, capital repairs, property availability, or tax documentation.</p>

                    <form method="POST" action="{{ route('owner.messages.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="property_id" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Related Property <span class="font-normal text-gray-400 lowercase">(optional)</span></label>
                            <select id="property_id" name="property_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- General Portfolio Matter --</option>
                                @foreach($properties as $property)
                                <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>
                                    {{ $property->title }}
                                </option>
                                @endforeach
                            </select>
                            @error('property_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Your Message</label>
                            <textarea id="message" name="message" rows="5" maxlength="3000" required placeholder="Describe your question or request in detail..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('message') }}</textarea>
                            @error('message')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-emerald-800 transition text-sm flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-envelope text-xs"></i> Send to Portfolio Manager
                        </button>
                    </form>
                </div>

                <!-- Support SLA Card -->
                <div class="mt-4 bg-slate-900 text-slate-200 rounded-lg p-4 text-xs space-y-2">
                    <div class="font-bold text-white flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-emerald-400"></i> Asset Management SLA
                    </div>
                    <p class="text-slate-300 leading-relaxed">
                        Inquiries submitted via the owner concierge are handled by dedicated portfolio managers. Response times average under 2 hours during normal business hours (8 AM - 6 PM WAT).
                    </p>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
