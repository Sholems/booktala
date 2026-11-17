{{-- Salary, bank details and recurring deductions (payroll managers only). --}}
@php $salary = $staff->salaryStructure; @endphp
<div id="pay" class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-1">Pay</h3>
        <p class="text-sm text-gray-500 mb-5">Monthly amounts. Used for each pay run from now on.</p>

        @if($errors->hasAny(['basic', 'housing', 'transport', 'other_allowance', 'overtime_rate', 'annual_rent', 'bank_account_number']))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            @foreach($errors->only(['basic', 'housing', 'transport', 'other_allowance', 'overtime_rate', 'annual_rent', 'bank_account_number']) as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.payroll.salary', $member) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                @foreach(['basic' => 'Basic', 'housing' => 'Housing', 'transport' => 'Transport', 'other_allowance' => 'Other allowance'] as $field => $label)
                <div>
                    <label class="block text-xs text-gray-600 mb-1">{{ $label }} (₦)</label>
                    <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $salary?->$field) }}" {{ $field === 'basic' ? 'required' : '' }} class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                @endforeach
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Overtime rate (₦ per hour)</label>
                    <input type="number" step="0.01" min="0" name="overtime_rate" value="{{ old('overtime_rate', $salary?->overtime_rate) }}" placeholder="No overtime pay" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Annual rent (for rent relief)</label>
                    <input type="number" step="0.01" min="0" name="annual_rent" value="{{ old('annual_rent', $salary?->annual_rent) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                @foreach(['pension_enabled' => 'Pension', 'nhf_enabled' => 'NHF', 'deduct_absences' => 'Deduct missed shifts'] as $field => $label)
                <label class="flex items-center gap-2">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $salary ? $salary->$field : $field !== 'nhf_enabled'))> {{ $label }}
                </label>
                @endforeach
            </div>
            <fieldset class="rounded-lg border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <legend class="px-1 text-sm font-medium text-gray-700">Bank &amp; statutory IDs</legend>
                <input type="text" name="bank_name" value="{{ old('bank_name', $staff->bank_name) }}" placeholder="Bank" class="border rounded-lg px-3 py-2 text-sm">
                <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $staff->bank_account_name) }}" placeholder="Account name" class="border rounded-lg px-3 py-2 text-sm">
                <input type="text" inputmode="numeric" maxlength="10" name="bank_account_number" value="{{ old('bank_account_number', $staff->bank_account_number) }}" placeholder="Account number (10 digits)" class="border rounded-lg px-3 py-2 text-sm">
                <input type="text" name="tax_id" value="{{ old('tax_id', $staff->tax_id) }}" placeholder="Tax ID (TIN)" class="border rounded-lg px-3 py-2 text-sm">
                <input type="text" name="pension_pin" value="{{ old('pension_pin', $staff->pension_pin) }}" placeholder="Pension PIN (RSA)" class="border rounded-lg px-3 py-2 text-sm">
            </fieldset>
            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">Save pay details</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-1">Loans &amp; deductions</h3>
        <p class="text-sm text-gray-500 mb-4">Taken from each pay run until the balance is cleared (or until stopped, if no balance).</p>
        <ul class="divide-y text-sm mb-5">
            @forelse($staff->deductions->sortByDesc('is_active') as $deduction)
            <li class="py-2 flex items-center justify-between gap-3 {{ $deduction->is_active ? '' : 'text-gray-400' }}">
                <div>
                    <div>{{ $deduction->description }} <span class="text-xs">({{ \App\Models\StaffDeduction::TYPES[$deduction->type] }})</span></div>
                    <div class="text-xs">₦{{ number_format($deduction->monthly_amount, 2) }}/month @if($deduction->balance !== null)· ₦{{ number_format($deduction->balance, 2) }} left @endif @unless($deduction->is_active)· ended @endunless</div>
                </div>
                @if($deduction->is_active)
                <form method="POST" action="{{ route('admin.payroll.deductions.stop', [$member, $deduction]) }}" onsubmit="return confirm('Stop this deduction?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-red-600 hover:underline">Stop</button>
                </form>
                @endif
            </li>
            @empty
            <li class="py-2 text-gray-500">None.</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('admin.payroll.deductions.store', $member) }}" class="grid grid-cols-2 gap-3">
            @csrf
            <select name="type" class="border rounded-lg px-3 py-2 text-sm">
                @foreach(\App\Models\StaffDeduction::TYPES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="description" required maxlength="255" placeholder="Description" class="border rounded-lg px-3 py-2 text-sm">
            <input type="number" name="monthly_amount" required step="0.01" min="1" placeholder="Per month (₦)" class="border rounded-lg px-3 py-2 text-sm">
            <input type="number" name="balance" step="0.01" min="1" placeholder="Total to repay (optional)" class="border rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="col-span-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Add deduction</button>
        </form>
    </div>
</div>
