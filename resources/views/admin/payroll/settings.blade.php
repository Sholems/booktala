@extends('layouts.admin')
@section('page_title', 'Payroll Rates')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.payroll.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Payroll</a>
    <h2 class="mt-1 text-2xl font-bold">Tax &amp; pension rates</h2>
</div>

<div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-900 max-w-3xl">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    The defaults follow the Nigeria Tax Act 2025 as generally published for 2026 (six PAYE bands from 0% to 25%, the first ₦800,000 a year tax-free, rent relief of 20% of rent up to ₦500,000, pension 8% + 10%, NHF 2.5%).
    <strong>Have your accountant confirm these before the first pay run.</strong> Changes apply to pay runs calculated afterwards; approved runs keep the rates they used.
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 max-w-3xl">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.payroll.settings.update') }}" class="space-y-6 max-w-3xl">
    @csrf
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-1">PAYE bands (annual)</h3>
        <p class="text-xs text-gray-500 mb-4">Each band taxes the next slice of annual taxable income. Leave the last band's width empty: it covers everything above.</p>
        <div class="space-y-2">
            @foreach(old('bands', array_map(fn ($b) => ['width' => $b[0], 'rate' => $b[1]], $settings['paye_bands'])) as $i => $band)
            <div class="grid grid-cols-2 gap-3 items-center">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-14">{{ $i === 0 ? 'First' : 'Next' }}</span>
                    <input type="number" step="0.01" min="0" name="bands[{{ $i }}][width]" value="{{ $band['width'] }}" placeholder="the rest" class="flex-1 border rounded-lg px-3 py-2 text-sm">
                    <span class="text-xs text-gray-500">₦</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">at</span>
                    <input type="number" step="0.01" min="0" max="100" name="bands[{{ $i }}][rate]" value="{{ $band['rate'] }}" required class="w-24 border rounded-lg px-3 py-2 text-sm">
                    <span class="text-xs text-gray-500">%</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach([
            ['rent_relief_rate', 'Rent relief (% of annual rent)', '0.01'],
            ['rent_relief_cap', 'Rent relief cap (₦ per year)', '1'],
            ['pension_employee_rate', 'Pension: employee %', '0.01'],
            ['pension_employer_rate', 'Pension: employer %', '0.01'],
            ['nhf_rate', 'NHF % of basic', '0.01'],
            ['working_days_per_month', 'Working days per month (absence rate)', '1'],
        ] as [$key, $label, $step])
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
            <input type="number" step="{{ $step }}" min="0" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
            <p class="text-xs text-gray-400 mt-1">Default: {{ $defaults[$key] }}</p>
        </div>
        @endforeach
    </div>

    <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">Save rates</button>
</form>
@endsection
