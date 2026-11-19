@extends('layouts.customer')
@use('App\Models\RoomServiceOrder')

@section('title', 'Order Details')
@section('page_title', 'In-Residence Request')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-800">Request #{{ $order->id }}</h2>
                    <span class="text-xs uppercase font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {{ RoomServiceOrder::orderTypes()[$order->order_type] ?? $order->order_type }}
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">{{ $order->reservation->property->name ?? 'N/A' }} (Unit {{ $order->reservation->unit->unit_number ?? 'Main' }})</p>
            </div>
            <span class="px-4 py-2 rounded-full text-xs font-bold 
                @if($order->status === 'delivered') bg-green-100 text-green-700
                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                @elseif($order->status === 'on_the_way') bg-purple-100 text-purple-700
                @else bg-red-100 text-red-700 @endif">
                {{ RoomServiceOrder::statuses()[$order->status] }}
            </span>
        </div>
    </div>

    <!-- Airport Pickup Info (if applicable) -->
    @if($order->order_type === 'airport_pickup' && ($order->pickup_location || $order->flight_number || $order->pickup_time))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
        <h3 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
            <i class="fas fa-plane-arrival"></i>
            Airport Transfer Details
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
                <span class="text-gray-500 block uppercase font-semibold">Location / Terminal</span>
                <span class="font-bold text-gray-800">{{ $order->pickup_location ?? 'Not specified' }}</span>
            </div>
            <div>
                <span class="text-gray-500 block uppercase font-semibold">Pickup Time</span>
                <span class="font-bold text-gray-800">{{ $order->pickup_time ? \Carbon\Carbon::parse($order->pickup_time)->format('M j, Y g:i A') : 'Flexible' }}</span>
            </div>
            <div>
                <span class="text-gray-500 block uppercase font-semibold">Flight No.</span>
                <span class="font-bold text-gray-800">{{ $order->flight_number ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Order Items -->
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-bold text-gray-800 mb-4">Requested Items / Services</h3>
        <div class="space-y-3">
            @foreach($order->items as $item)
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="font-medium text-gray-800 text-sm">{{ $item['name'] }}</p>
                    <p class="text-xs text-gray-500">Qty: {{ $item['quantity'] }}</p>
                </div>
                @if(($item['price'] ?? 0) > 0)
                <p class="font-bold text-gray-800 text-sm">₦{{ number_format($item['quantity'] * $item['price'], 2) }}</p>
                @endif
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
            <div>
                <span class="font-bold text-gray-800">Total</span>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        Method: {{ ucfirst($order->payment_method ?? 'folio') }}
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $order->payment_status === 'charged_to_folio' ? 'bg-blue-100 text-blue-700' : ($order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ str_replace('_', ' ', ucfirst($order->payment_status ?? 'unpaid')) }}
                    </span>
                </div>
            </div>
            @if((float) $order->total_amount > 0)
            <span class="text-2xl font-black text-primary">₦{{ number_format($order->total_amount, 2) }}</span>
            @else
            <span class="text-sm text-gray-500">{{ $order->status === 'delivered' ? 'No charge' : 'To be confirmed by our team' }}</span>
            @endif
        </div>
    </div>

    <!-- Special Instructions -->
    @if($order->special_instructions)
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-bold text-gray-800 mb-2">Special Instructions</h3>
        <p class="text-gray-600 text-sm">{{ $order->special_instructions }}</p>
    </div>
    @endif

    <!-- Timeline -->
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6 shadow-sm">
        <h3 class="text-base font-bold text-gray-800 mb-4">Order Timeline</h3>
        <div class="relative pl-8 border-l-2 border-gray-200 space-y-6">
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800 text-sm">Order Placed</p>
                <p class="text-xs text-gray-500">{{ $order->created_at->format('M j, Y g:i A') }}</p>
            </div>
            @if($order->status !== 'pending' && $order->status !== 'cancelled')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full {{ in_array($order->status, ['preparing', 'on_the_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300' }} border-2 border-white"></div>
                <p class="font-medium text-gray-800 text-sm">Preparing</p>
                @if($order->status === 'preparing')
                <p class="text-xs text-gray-500">Your order is being prepared / arranged</p>
                @endif
            </div>
            @endif
            @if(in_array($order->status, ['on_the_way', 'delivered']))
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800 text-sm">On the Way</p>
            </div>
            @endif
            @if($order->status === 'delivered')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800 text-sm">Delivered / Completed</p>
                <p class="text-xs text-gray-500">{{ $order->delivered_at ? $order->delivered_at->format('M j, Y g:i A') : '' }}</p>
            </div>
            @endif
            @if($order->status === 'cancelled')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-red-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800 text-sm">Cancelled</p>
                <p class="text-xs text-gray-500">Order was cancelled</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-4">
        <a href="{{ route('customer.room-service.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Back to Orders
        </a>
        @if(in_array($order->status, ['pending', 'preparing']))
        <form action="{{ route('customer.room-service.cancel', $order) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?')">
            @csrf
            <button type="submit" class="px-6 py-2.5 border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition text-sm">
                <i class="fas fa-times mr-2"></i> Cancel Request
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
