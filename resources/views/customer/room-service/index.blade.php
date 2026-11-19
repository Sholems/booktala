@extends('layouts.customer')
@use('App\Models\RoomServiceOrder')

@section('title', 'Room Service')
@section('page_title', 'Room Service')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Your Orders</h2>
            <p class="text-gray-500 text-sm">Manage your room service requests</p>
        </div>
        <a href="{{ route('customer.room-service.create') }}" class="px-5 py-2.5 bg-accent text-white font-bold rounded-lg hover:bg-accent-dark transition">
            <i class="fas fa-plus mr-2"></i>New Order
        </a>
    </div>

    @if($orders->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-concierge-bell text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">No Orders Yet</h3>
        <p class="text-gray-500 mb-6">You haven't placed any room service orders yet.</p>
        <a href="{{ route('customer.room-service.create') }}" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-light transition">
            Place Your First Order
        </a>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Property</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-{{ $order->order_type === 'food' ? 'orange' : ($order->order_type === 'laundry' ? 'blue' : 'purple') }}-100 flex items-center justify-center">
                                <i class="fas {{ $order->order_type === 'food' ? 'fa-utensils' : ($order->order_type === 'laundry' ? 'fa-tshirt' : ($order->order_type === 'amenity' ? 'fa-spa' : ($order->order_type === 'maintenance' ? 'fa-wrench' : 'fa-box'))) }} text-{{ $order->order_type === 'food' ? 'orange' : ($order->order_type === 'laundry' ? 'blue' : 'purple') }}-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ RoomServiceOrder::orderTypes()[$order->order_type] }}</p>
                                <p class="text-xs text-gray-500">{{ count($order->items) }} item(s)</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $order->reservation->property->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-800">
                        ₦{{ number_format($order->total_amount, 2) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            @if($order->status === 'delivered') bg-green-100 text-green-700
                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                            @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                            @elseif($order->status === 'on_the_way') bg-purple-100 text-purple-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ RoomServiceOrder::statuses()[$order->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $order->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('customer.room-service.show', $order) }}" class="text-primary hover:text-accent font-medium text-sm">
                            View <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
