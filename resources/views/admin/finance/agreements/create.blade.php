@extends('layouts.admin')
@section('page_title', 'Draft Management Agreement')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
        <a href="{{ route('admin.agreements.index') }}" class="hover:text-primary">Agreements</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span>Draft New Agreement</span>
    </div>
    <h2 class="text-2xl font-bold">Draft Management Agreement</h2>
    <p class="text-sm text-gray-500">Create a new effective-dated commercial contract for a property unit.</p>
</div>

@if($errors->any())
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-lg p-4 text-sm text-rose-800">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="max-w-3xl bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form action="{{ route('admin.agreements.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="unit_id" class="block text-sm font-semibold text-slate-700 mb-1">Unit / Property *</label>
                <select name="unit_id" id="unit_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select a unit...</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected(old('unit_id', $selectedUnitId) == $unit->id)>
                        {{ $unit->title }} ({{ $unit->unit_code }}) · {{ $unit->site->name ?? '' }}
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Select the specific unit governed by this agreement.</p>
            </div>

            <div>
                <label for="owner_id" class="block text-sm font-semibold text-slate-700 mb-1">Owner *</label>
                <select name="owner_id" id="owner_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select the owner...</option>
                    @foreach($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>
                        {{ $owner->name }} ({{ $owner->type }})
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">The legal property owner entitled to payouts.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="commission_rate" class="block text-sm font-semibold text-slate-700 mb-1">Commission Rate (%) *</label>
                <input type="number" step="0.01" min="0" max="100" name="commission_rate" id="commission_rate" value="{{ old('commission_rate', '20.00') }}" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-slate-500 mt-1">Management fee percentage retained by BookTala.</p>
            </div>

            <div>
                <label for="effective_from" class="block text-sm font-semibold text-slate-700 mb-1">Effective From *</label>
                <input type="date" name="effective_from" id="effective_from" value="{{ old('effective_from', today()->toDateString()) }}" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-slate-500 mt-1">Start date for this agreement.</p>
            </div>

            <div>
                <label for="effective_to" class="block text-sm font-semibold text-slate-700 mb-1">Effective To (Optional)</label>
                <input type="date" name="effective_to" id="effective_to" value="{{ old('effective_to') }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-slate-500 mt-1">Leave empty if agreement has an open-ended duration.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-slate-100">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Expense Deduction Treatment</label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="deduct_expenses" value="0">
                    <input type="checkbox" name="deduct_expenses" value="1" @checked(old('deduct_expenses', '1') == '1') class="rounded border-slate-300 text-primary focus:ring-primary">
                    <span class="text-sm text-slate-700">Deduct property maintenance expenses from owner payout</span>
                </label>
            </div>

            <div>
                <label for="caution_forfeiture_recipient" class="block text-sm font-semibold text-slate-700 mb-1">Caution Forfeiture Recipient</label>
                <select name="caution_forfeiture_recipient" id="caution_forfeiture_recipient" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="company" @selected(old('caution_forfeiture_recipient', 'company') === 'company')>BookTala (Company)</option>
                    <option value="owner" @selected(old('caution_forfeiture_recipient') === 'owner')>Property Owner</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">Who receives forfeited guest damage deposits.</p>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1">Notes / Terms (Optional)</label>
            <textarea name="notes" id="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('admin.agreements.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-medium transition">
                Cancel
            </a>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg text-sm font-medium shadow-sm transition">
                Create Draft Agreement
            </button>
        </div>
    </form>
</div>
@endsection
