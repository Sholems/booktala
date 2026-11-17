@extends('layouts.owner')
@section('page_title', 'Statement')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <a href="{{ route('owner.statements.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> All statements</a>
        <h2 class="mt-1 text-2xl font-bold">{{ $statement->periodLabel() }}</h2>
        <p class="text-sm text-gray-500">{{ $statement->status === 'paid' ? 'Paid ' . $statement->paid_at->format('j M Y') . ($statement->payment_reference ? ' · ref ' . $statement->payment_reference : '') : 'Approved · payout pending' }}</p>
    </div>
    <a href="{{ route('owner.statements.pdf', $statement) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-file-pdf mr-1"></i> Download PDF</a>
</div>
@include('statements._body', ['statement' => $statement])
@endsection
