@extends('layouts.owner')
@section('page_title', 'Statements')
@section('content')
<div class="max-w-3xl">
    <h2 class="text-xl font-bold mb-4">Monthly statements</h2>
    <div class="bg-white rounded-lg shadow divide-y">
        @forelse($statements as $statement)
        <a href="{{ route('owner.statements.show', $statement) }}" class="flex items-center justify-between p-4 hover:bg-gray-50">
            <div>
                <div class="font-medium">{{ $statement->periodLabel() }}</div>
                <div class="text-xs text-gray-500">{{ $statement->nights_booked }} nights · {{ $statement->status === 'paid' ? 'Paid ' . $statement->paid_at->format('j M Y') : 'Payout pending' }}</div>
            </div>
            <span class="font-semibold">₦{{ number_format($statement->net_payout, 2) }}</span>
        </a>
        @empty
        <p class="p-6 text-center text-gray-500">No statements yet. They appear here each month once approved.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $statements->links() }}</div>
</div>
@endsection
