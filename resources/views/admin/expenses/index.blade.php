@extends('layouts.admin')
@section('page_title', 'Expenses')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Property Expenses</h2>
        <p class="text-sm text-gray-500">Owner expenses are deducted on the owner's next statement; company expenses are G-Briel's own cost.</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="border rounded-lg px-3 py-2 text-sm">
        <select name="property_id" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">All properties</option>
            @foreach($properties as $property)
            <option value="{{ $property->id }}" @selected((int) request('property_id') === $property->id)>{{ $property->title }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm">Show</button>
    </form>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">Charged to owners · {{ $month->format('M Y') }}</div>
                <div class="text-xl font-bold">₦{{ number_format($totals['owner'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">Company cost · {{ $month->format('M Y') }}</div>
                <div class="text-xl font-bold">₦{{ number_format($totals['company'] ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
            <table class="min-w-full mobile-card-table text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expense</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charged to</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($expenses as $expense)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap" data-label="Date">{{ $expense->spent_on->format('j M') }}</td>
                        <td class="px-4 py-3" data-label="Property">{{ $expense->property->title ?? '' }}</td>
                        <td class="px-4 py-3" data-label="Expense">
                            <div>{{ $expense->description }}</div>
                            <div class="text-xs text-gray-500">{{ $expense->categoryLabel() }}@if($expense->receipt_path) · <a href="{{ route('admin.expenses.receipt', $expense) }}" class="text-primary hover:underline">receipt</a>@endif</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap" data-label="Amount">₦{{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-3" data-label="Charged to">
                            @if($expense->charge_to === 'owner')
                            {{ $expense->property->owner->name ?? 'Owner' }}
                            @if($expense->statement)<div class="text-xs text-gray-500">On {{ $expense->statement->periodLabel() }} statement</div>@endif
                            @else
                            Company
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right" data-label="">
                            @unless($expense->isLocked())
                            <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No expenses recorded for {{ $month->format('F Y') }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenses->links() }}
    </div>

    <div class="bg-white rounded-lg shadow p-6 h-fit">
        <h3 class="text-lg font-bold mb-4">Record an expense</h3>
        <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Property</label>
                <select name="property_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Choose…</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" @selected((int) old('property_id', request('property_id')) === $property->id)>{{ $property->title }} ({{ $property->owner->name ?? '—' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" required class="w-full border rounded-lg px-3 py-2 text-sm">
                        @foreach(\App\Models\Expense::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="spent_on" required max="{{ today()->toDateString() }}" value="{{ old('spent_on', today()->toDateString()) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" required maxlength="255" value="{{ old('description') }}" placeholder="e.g. Replaced AC compressor" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₦)</label>
                    <input type="number" name="amount" required step="0.01" min="0.01" value="{{ old('amount') }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charge to</label>
                    <select name="charge_to" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="owner" @selected(old('charge_to', 'owner') === 'owner')>Owner</option>
                        <option value="company" @selected(old('charge_to') === 'company')>Company</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Related repair <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="maintenance_report_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">None</option>
                    @foreach($repairs as $repair)
                    <option value="{{ $repair->id }}" @selected((int) old('maintenance_report_id') === $repair->id)>{{ $repair->title }} — {{ $repair->property->title ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Receipt <span class="text-gray-400 font-normal">(photo or PDF, optional)</span></label>
                <input type="file" name="receipt" accept="image/*,application/pdf" class="w-full text-sm">
            </div>
            <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Save expense</button>
        </form>
    </div>
</div>
@endsection
