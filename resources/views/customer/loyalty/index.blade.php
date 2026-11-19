@extends('layouts.customer')

@section('title', 'Loyalty Points')
@section('page_title', 'Loyalty Points')

@section('content')
<div class="space-y-6">
    <!-- Points Balance Card -->
    <div class="bg-gradient-to-br from-primary to-primary-light rounded-2xl p-6 lg:p-8 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <p class="text-white/60 text-sm font-medium uppercase tracking-wider mb-1">Your Balance</p>
                <p class="text-5xl lg:text-6xl font-black">{{ number_format($profile->loyalty_points) }}</p>
                <p class="text-white/60 text-sm mt-2">loyalty points</p>
            </div>
            <div class="flex flex-col items-start lg:items-end">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-900/30 text-amber-400 font-bold text-lg capitalize">
                    <i class="fas fa-crown"></i>
                    {{ $profile->loyalty_tier }} Member
                </span>
                @if($currentTier['discount'] > 0)
                    <p class="text-white/60 text-sm mt-2">{{ $currentTier['discount'] }}% discount on all bookings</p>
                @endif
            </div>
        </div>
        
        @if($nextTier)
        <div class="mt-6 pt-6 border-t border-white/10">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-white/60">Progress to {{ $nextTier['name'] }}</span>
                <span class="text-sm font-medium text-white">{{ number_format($nextTier['points_needed']) }} points needed</span>
            </div>
            <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                @php
                    $progress = min(100, (($profile->loyalty_points - $currentTier['min_points']) / ($nextTier['min_points'] - $currentTier['min_points'])) * 100);
                @endphp
                <div class="h-full bg-gradient-to-r from-accent to-[#e6c86e] rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        @endif
    </div>

    <!-- Tier Benefits -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $tierColors = [
                'bronze' => ['bg' => 'orange-100', 'text' => 'orange-600'],
                'silver' => ['bg' => 'gray-100', 'text' => 'gray-600'],
                'gold' => ['bg' => 'yellow-100', 'text' => 'yellow-600'],
                'platinum' => ['bg' => 'indigo-100', 'text' => 'indigo-600'],
            ];
        @endphp
        @foreach($tiers as $key => $tier)
        <div class="bg-white rounded-xl p-5 border-2 {{ $profile->loyalty_tier === $key ? 'border-accent ring-2 ring-accent/20' : 'border-gray-100' }}">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center bg-{{ $tierColors[$key]['bg'] }}">
                    <i class="fas fa-crown text-{{ $tierColors[$key]['text'] }}"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 capitalize">{{ $tier['name'] }}</h3>
                    <p class="text-xs text-gray-500">{{ number_format($tier['min_points']) }}+ points</p>
                </div>
            </div>
            <p class="text-sm text-gray-600">{{ $tier['discount'] }}% discount on all bookings</p>
        </div>
        @endforeach
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-100">
            <p class="text-gray-500 text-sm">Total Stays</p>
            <p class="text-2xl font-black text-gray-800">{{ $profile->total_stays }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100">
            <p class="text-gray-500 text-sm">Total Nights</p>
            <p class="text-2xl font-black text-gray-800">{{ $profile->total_nights }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100">
            <p class="text-gray-500 text-sm">Total Spent</p>
            <p class="text-2xl font-black text-gray-800">₦{{ number_format($profile->total_spent) }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-100">
            <p class="text-gray-500 text-sm">Member Since</p>
            <p class="text-2xl font-black text-gray-800">{{ $profile->created_at->format('Y') }}</p>
        </div>
    </div>

    <!-- Redeem Points -->
    <div class="bg-white rounded-xl p-6 border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Redeem Points</h3>
        <form action="{{ route('customer.loyalty.redeem') }}" method="POST" class="flex flex-col sm:flex-row gap-4">
            @csrf
            <input type="number" name="points" min="100" step="100" placeholder="Enter points (min 100)" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent">
            <button type="submit" class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-light transition">
                Redeem for ₦Discount
            </button>
        </form>
        <p class="text-sm text-gray-500 mt-3">100 points = ₦1.00 discount • Points can be redeemed for any future booking</p>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Points History</h3>
        </div>
        @if($transactions->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-history text-4xl mb-3 opacity-30"></i>
                <p>No transactions yet</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($transactions as $transaction)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $transaction->points > 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            <i class="fas {{ $transaction->points > 0 ? 'fa-plus' : 'fa-minus' }}"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $transaction->description }}</p>
                            <p class="text-sm text-gray-500">{{ $transaction->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                    <span class="font-bold {{ $transaction->points > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                    </span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection