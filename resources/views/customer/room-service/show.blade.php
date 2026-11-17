@extends('layouts.customer')
@use('App\Models\RoomServiceOrder')

@section('title', 'Order Details')
@section('page_title', 'Room Service')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Order #{{ $order->id }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $order->reservation->property->name ?? 'N/A' }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-bold 
                @if($order->status === 'delivered') bg-green-100 text-green-700
                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                @elseif($order->status === 'on_the_way') bg-purple-100 text-purple-700
                @else bg-red-100 text-red-700 @endif">
                {{ RoomServiceOrder::statuses()[$order->status] }}
            </span>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Order Items</h3>
        <div class="space-y-3">
            @foreach($order->items as $item)
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                    <p class="text-sm text-gray-500">Qty: {{ $item['quantity'] }}</p>
                </div>
                @if(($item['price'] ?? 0) > 0)
                <p class="font-bold text-gray-800">₦{{ number_format($item['quantity'] * $item['price'], 2) }}</p>
                @endif
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
            <span class="font-bold text-gray-800">Total</span>
            @if((float) $order->total_amount > 0)
            <span class="text-2xl font-black text-[#0B2D5B]">₦{{ number_format($order->total_amount, 2) }}</span>
            @else
            <span class="text-sm text-gray-500">{{ $order->status === 'delivered' ? 'No charge' : 'To be confirmed by our team' }}</span>
            @endif
        </div>
    </div>

    <!-- Special Instructions -->
    @if($order->special_instructions)
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Special Instructions</h3>
        <p class="text-gray-600">{{ $order->special_instructions }}</p>
    </div>
    @endif

    <!-- Timeline -->
    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Order Timeline</h3>
        <div class="relative pl-8 border-l-2 border-gray-200 space-y-6">
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800">Order Placed</p>
                <p class="text-sm text-gray-500">{{ $order->created_at->format('M j, Y g:i A') }}</p>
            </div>
            @if($order->status !== 'pending' && $order->status !== 'cancelled')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full {{ in_array($order->status, ['preparing', 'on_the_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300' }} border-2 border-white"></div>
                <p class="font-medium text-gray-800">Preparing</p>
                @if($order->status === 'preparing')
                <p class="text-sm text-gray-500">Your order is being prepared</p>
                @endif
            </div>
            @endif
            @if(in_array($order->status, ['on_the_way', 'delivered']))
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800">On the Way</p>
            </div>
            @endif
            @if($order->status === 'delivered')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800">Delivered</p>
                <p class="text-sm text-gray-500">{{ $order->delivered_at->format('M j, Y g:i A') }}</p>
            </div>
            @endif
            @if($order->status === 'cancelled')
            <div class="relative">
                <div class="absolute -left-9 w-4 h-4 rounded-full bg-red-500 border-2 border-white"></div>
                <p class="font-medium text-gray-800">Cancelled</p>
                <p class="text-sm text-gray-500">Order was cancelled</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-4">
        <a href="{{ route('customer.room-service.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        @if(in_array($order->status, ['pending', 'preparing']))
        <form action="{{ route('customer.room-service.cancel', $order) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            <button type="submit" class="px-6 py-3 border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition">
                <i class="fas fa-times mr-2"></i> Cancel Order
            </button>
        </form>
        @endif
    </div>
</div>
@endsection