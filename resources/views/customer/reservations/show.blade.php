@extends('layouts.customer')

@section('page_title', 'Booking Details')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('customer.reservations.index') }}" class="text-blue-600 hover:underline">&larr; Back to Reservations</a>
        </div>
        
        <div class="mb-6 p-6 bg-white rounded-lg shadow">
            @include('customer.partials.journey', ['reservation' => $reservation])
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="mb-4 text-xl font-semibold">Reservation Details</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Confirmation Code:</span>
                        <span class="font-medium">{{ $reservation->confirmation_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $reservation->status == 'confirmed' ? 'bg-green-100 text-green-800' : 
                               ($reservation->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                               ($reservation->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                            {{ ucfirst($reservation->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Check-in:</span>
                        <span class="font-medium">{{ $reservation->check_in->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Check-out:</span>
                        <span class="font-medium">{{ $reservation->check_out->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Guests:</span>
                        <span class="font-medium">{{ $reservation->guests }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Price:</span>
                        <span class="font-medium">₦{{ number_format((float) $reservation->total_amount) }}</span>
                    </div>
                    @if($reservation->refund_status)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Refund:</span>
                        <span class="font-medium">{{ ['pending' => 'Under review', 'refunded' => 'Refunded', 'declined' => 'Not applicable'][$reservation->refund_status] ?? ucfirst($reservation->refund_status) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="mb-4 text-xl font-semibold">Property</h2>
                <h3 class="mb-2 text-lg font-medium">{{ $reservation->property->name ?? 'N/A' }}</h3>
                <p class="text-gray-600">{{ $reservation->property->address ?? '' }}</p>
                <a href="{{ route('properties.show', $reservation->property->slug) }}" class="mt-2 inline-block text-blue-600 hover:underline">View Property</a>
            </div>
        </div>
        
        <div class="mt-6 p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-xl font-semibold">Payment History</h2>
            @forelse($reservation->payments as $payment)
            <div class="flex justify-between items-center py-3 border-b">
                <div>
                    <span class="font-medium">₦{{ number_format($payment->amount) }}</span>
                    <span class="ml-2 text-sm text-gray-500">{{ $payment->payment_method }}</span>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                    {{ $payment->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
            @empty
            <p class="text-gray-500">No payments yet</p>
            @endforelse
        </div>
        
        @if(in_array($reservation->booking_status, ['pending', 'confirmed'], true))
        <div class="mt-6 p-6 bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold">Need to cancel?</h2>
                    @if($reservation->check_in->isAfter(today()))
                    <p class="text-sm text-gray-600">You can cancel online until the day before check-in.</p>
                    @else
                    <p class="text-sm text-gray-600">Online cancellation has closed for this booking. Please call us on {{ \App\Models\Setting::get('phone', config('brand.phone')) }}.</p>
                    @endif
                </div>
                @if($reservation->check_in->isAfter(today()))
                <button type="button" @click="open = !open" class="px-4 py-2 rounded border border-red-600 text-red-600 hover:bg-red-50">Cancel booking</button>
                @endif
            </div>

            @if($reservation->check_in->isAfter(today()))
            <div x-show="open" style="display: none" class="mt-5 border-t pt-5">
                <h3 class="font-semibold mb-2">Cancellation policy</h3>
                <div class="text-sm text-gray-700 bg-gray-50 rounded p-4 mb-4 whitespace-pre-line">{{ $reservation->property->cancellation_policy ?: 'See our cancellation policy page for details.' }}</div>
                <p class="text-sm text-gray-600 mb-4">
                    <a href="{{ route('pages.cancellation') }}" class="text-blue-600 hover:underline" target="_blank">Read the full cancellation policy</a>.
                    @if(in_array($reservation->payment_status, ['paid', 'partial'], true))
                    You have paid for this booking, so any refund due under the policy will be reviewed by our team after you cancel.
                    @endif
                </p>
                <form action="{{ route('customer.reservations.cancel', $reservation) }}" method="POST" class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium">Reason (optional)</label>
                    <textarea name="reason" rows="3" maxlength="500" class="w-full p-2 border rounded" placeholder="Let us know why you are cancelling"></textarea>
                    <button type="submit" class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700" onclick="return confirm('Cancel this booking? This cannot be undone.')">Confirm cancellation</button>
                </form>
            </div>
            @endif
        </div>
        @endif

        @if($reservation->status == 'checked_out' && !$reservation->review)
        <div id="review" class="mt-6 p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-xl font-semibold">Leave a Review</h2>
            <form action="{{ route('customer.reviews.store', $reservation) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium">Rating</label>
                    <select name="overall_rating" class="w-full p-2 border rounded" required>
                        <option value="">Select Rating</option>
                        <option value="5">5 Stars - Excellent</option>
                        <option value="4">4 Stars - Very Good</option>
                        <option value="3">3 Stars - Good</option>
                        <option value="2">2 Stars - Fair</option>
                        <option value="1">1 Star - Poor</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium">Comment (optional)</label>
                    <textarea name="comment" rows="3" maxlength="1000" class="w-full p-2 border rounded" placeholder="Share your experience..."></textarea>
                </div>
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Submit Review</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
