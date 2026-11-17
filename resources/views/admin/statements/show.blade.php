@extends('layouts.admin')
@section('page_title', 'Owner Statement')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <a href="{{ route('admin.statements.index', ['month' => $statement->period_start->format('Y-m')]) }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> All statements</a>
        <h2 class="mt-1 text-2xl font-bold">{{ $statement->owner->name }} · {{ $statement->periodLabel() }}</h2>
        <p class="text-sm text-gray-500">
            {{ \App\Models\OwnerStatement::STATUSES[$statement->status] }}
            @if($statement->approved_at) · approved {{ $statement->approved_at->format('j M Y') }} by {{ $statement->approvedBy->name ?? '' }}@endif
            @if($statement->paid_at) · paid {{ $statement->paid_at->format('j M Y') }}@if($statement->payment_reference) (ref {{ $statement->payment_reference }})@endif @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.statements.pdf', $statement) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
        @if($statement->isDraft())
        <form method="POST" action="{{ route('admin.statements.generate') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $statement->period_start->format('Y-m') }}">
            <input type="hidden" name="owner_id" value="{{ $statement->owner_id }}">
            <button type="submit" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-sync mr-1"></i> Recalculate</button>
        </form>
        <form method="POST" action="{{ route('admin.statements.approve', $statement) }}" onsubmit="return confirm('Approve this statement? It will be frozen and shown to the owner.')">
            @csrf @method('PATCH')
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90">Approve</button>
        </form>
        @elseif($statement->status === 'approved')
        <form method="POST" action="{{ route('admin.statements.paid', $statement) }}" class="flex gap-2">
            @csrf @method('PATCH')
            <input type="text" name="payment_reference" placeholder="Transfer reference" maxlength="100" class="border rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">Record payout</button>
        </form>
        @endif
    </div>
</div>

@include('statements._body', ['statement' => $statement])

@if($statement->isDraft())
<form method="POST" action="{{ route('admin.statements.destroy', $statement) }}" class="mt-6" onsubmit="return confirm('Delete this draft?')">
    @csrf @method('DELETE')
    <button type="submit" class="text-sm text-red-600 hover:underline">Delete draft</button>
</form>
@endif
@endsection
