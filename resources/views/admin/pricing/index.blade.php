@extends('layouts.admin')
@section('page_title', 'Dynamic Pricing & Seasonal Rates')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Dynamic Pricing & Seasonal Rates</h2>
        <p class="text-sm text-gray-500">Automate rate adjustments for holiday surges, weekend premiums, low-season discounts & minimum stay rules.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3" x-data>
        <a href="{{ route('admin.pricing-rules.simulator') }}" class="inline-flex items-center gap-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fas fa-calendar-alt text-slate-500"></i>
            <span>Rate Simulator</span>
        </a>
        <button @click="$dispatch('open-pricing-modal')" type="button" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fas fa-plus"></i>
            <span>New Pricing Rule</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

<!-- Rules Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6" x-data="{ modalOpen: false, isEditing: false, ruleForm: { id: null, name: '', type: 'seasonal', property_id: '', site_id: '', start_date: '', end_date: '', adjustment_type: 'percentage_markup', adjustment_value: '', min_nights: '', priority: 0 } }" @open-pricing-modal.window="modalOpen = true; isEditing = false; ruleForm = { id: null, name: '', type: 'seasonal', property_id: '', site_id: '', start_date: '', end_date: '', adjustment_type: 'percentage_markup', adjustment_value: '', min_nights: '', priority: 0 }">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">Rule Name</th>
                    <th class="py-3 px-4">Scope</th>
                    <th class="py-3 px-4">Effective Dates</th>
                    <th class="py-3 px-4">Adjustment</th>
                    <th class="py-3 px-4 text-center">Min Stay</th>
                    <th class="py-3 px-4 text-center">Priority</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rules as $rule)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="py-3 px-4 font-medium text-slate-800">
                        <div>{{ $rule->name }}</div>
                        <span class="text-xs px-2 py-0.5 rounded font-medium {{ $rule->type === 'seasonal' ? 'bg-indigo-50 text-indigo-700' : ($rule->type === 'weekend' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                            {{ ucfirst($rule->type) }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        @if($rule->unit)
                        <div class="text-slate-800 font-medium">{{ $rule->unit->title }}</div>
                        <span class="text-xs text-slate-400">Unit-specific</span>
                        @elseif($rule->site)
                        <div class="text-slate-800 font-medium">{{ $rule->site->name }}</div>
                        <span class="text-xs text-slate-400">Site-wide</span>
                        @else
                        <span class="text-slate-700 font-medium">All Properties</span>
                        <div class="text-xs text-slate-400">Portfolio Global</div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-xs">
                        <div class="font-medium text-slate-700">{{ $rule->start_date?->format('M j, Y') }} — {{ $rule->end_date?->format('M j, Y') }}</div>
                        @if(!empty($rule->days_of_week))
                        <span class="text-[10px] text-slate-400">Selected days only</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        @switch($rule->adjustment_type)
                            @case('percentage_markup')
                                <span class="font-semibold text-rose-600">+{{ $rule->adjustment_value }}%</span>
                                <span class="text-xs text-slate-400">Surge</span>
                                @break
                            @case('percentage_discount')
                                <span class="font-semibold text-emerald-600">-{{ $rule->adjustment_value }}%</span>
                                <span class="text-xs text-slate-400">Discount</span>
                                @break
                            @case('fixed_markup')
                                <span class="font-semibold text-indigo-600">+₦{{ number_format($rule->adjustment_value) }}</span>
                                <span class="text-xs text-slate-400">Premium</span>
                                @break
                            @case('fixed_override')
                                <span class="font-semibold text-slate-800">₦{{ number_format($rule->price_override ?: $rule->adjustment_value) }}</span>
                                <span class="text-xs text-slate-400">Fixed Rate</span>
                                @break
                            @default
                                <span class="font-semibold text-slate-700">{{ $rule->adjustment_type }}</span>
                        @endswitch
                    </td>
                    <td class="py-3 px-4 text-center">
                        {{ $rule->min_nights ? $rule->min_nights.' nights' : '—' }}
                    </td>
                    <td class="py-3 px-4 text-center font-mono text-xs">
                        {{ $rule->priority }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form method="POST" action="{{ route('admin.pricing-rules.toggle', $rule) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $rule->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                {{ $rule->is_active ? 'Active' : 'Paused' }}
                            </button>
                        </form>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <form method="POST" action="{{ route('admin.pricing-rules.destroy', $rule) }}" onsubmit="return confirm('Delete this pricing rule?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Delete">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-8 text-center text-slate-400">
                        <i class="fas fa-tags text-3xl mb-2 text-slate-300"></i>
                        <p>No pricing rules configured. Create a rule to automate seasonal rates or holiday surges.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rules->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $rules->links() }}
    </div>
    @endif

    <!-- Create/Edit Modal -->
    <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-lg w-full p-6" @click.outside="modalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Create Dynamic Pricing Rule</h3>
                <button @click="modalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.pricing-rules.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rule Name</label>
                    <input type="text" name="name" required placeholder="e.g. December Festive Surge, Easter Weekend" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rule Type</label>
                        <select name="type" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="seasonal">Seasonal Surge / Discount</option>
                            <option value="weekend">Weekend Premium</option>
                            <option value="event">Special Event / Holiday</option>
                            <option value="length_of_stay">Length of Stay</option>
                            <option value="promo">Promo Rate</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Scope</label>
                        <select name="property_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Global (All Units)</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->title }} ({{ $unit->unit_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">End Date</label>
                        <input type="date" name="end_date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Type</label>
                        <select name="adjustment_type" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="percentage_markup">Percentage Increase (+%)</option>
                            <option value="percentage_discount">Percentage Discount (-%)</option>
                            <option value="fixed_markup">Fixed Daily Add-on (+₦)</option>
                            <option value="fixed_override">Fixed Nightly Rate (₦)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Value</label>
                        <input type="number" step="0.01" min="0" name="adjustment_value" required placeholder="e.g. 25 for +25%" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Stay (Nights)</label>
                        <input type="number" min="1" max="365" name="min_nights" placeholder="e.g. 3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                        <span class="text-[10px] text-slate-400">Enforced during these dates</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Priority Order</label>
                        <input type="number" min="0" max="100" name="priority" value="10" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                        <span class="text-[10px] text-slate-400">Higher numbers override lower</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs font-medium shadow-sm transition">
                        Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
