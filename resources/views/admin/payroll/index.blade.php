@extends('layouts.admin')
@section('page_title', 'Payroll')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Payroll</h2>
        <p class="text-sm text-gray-500">Monthly pay runs from each person's salary, attendance (overtime, absences) and deductions.</p>
    </div>
    <div class="flex flex-wrap gap-2 items-end">
        <a href="{{ route('admin.payroll.settings') }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-sliders-h mr-1"></i> Tax &amp; pension rates</a>
        <form method="POST" action="{{ route('admin.payroll.generate') }}" class="flex gap-2">
            @csrf
            <input type="month" name="month" required max="{{ now()->format('Y-m') }}" value="{{ now()->format('Y-m') }}" class="border rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90"><i class="fas fa-calculator mr-1"></i> Calculate pay run</button>
        </form>
    </div>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

@if($withoutSalary->isNotEmpty())
<div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
    <strong>{{ $withoutSalary->count() }} active staff have no salary set</strong> and will be left out of pay runs:
    @foreach($withoutSalary->take(8) as $member)
    <a href="{{ route('admin.staff.edit', $member->user_id) }}#pay" class="underline">{{ $member->user->name ?? $member->employee_id }}</a>@if(!$loop->last), @endif
    @endforeach
    @if($withoutSalary->count() > 8) and {{ $withoutSalary->count() - 8 }} more @endif.
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deductions</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net pay</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Employer pension</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($runs as $run)
            <tr>
                <td class="px-4 py-3" data-label="Month"><a href="{{ route('admin.payroll.show', $run) }}" class="text-primary hover:underline font-medium">{{ $run->periodLabel() }}</a></td>
                <td class="px-4 py-3 text-right" data-label="Gross">₦{{ number_format($run->total_gross, 2) }}</td>
                <td class="px-4 py-3 text-right" data-label="Deductions">₦{{ number_format($run->total_deductions, 2) }}</td>
                <td class="px-4 py-3 text-right font-semibold" data-label="Net pay">₦{{ number_format($run->total_net, 2) }}</td>
                <td class="px-4 py-3 text-right" data-label="Employer pension">₦{{ number_format($run->total_employer_pension, 2) }}</td>
                <td class="px-4 py-3" data-label="Status">
                    @php $badge = ['draft' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-blue-100 text-blue-800', 'paid' => 'bg-green-100 text-green-800'][$run->status]; @endphp
                    <span class="text-xs px-2 py-1 rounded-full {{ $badge }}">{{ \App\Models\PayRun::STATUSES[$run->status] }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No pay runs yet. Set salaries on each staff member's page, then calculate a pay run.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $runs->links() }}</div>
@endsection
