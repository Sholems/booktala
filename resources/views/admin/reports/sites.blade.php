@extends('layouts.admin')
@section('page_title', 'Site Report')
@section('content')
@php
    $num = fn ($v, $d = 0) => $v === null ? '—' : number_format($v, $d);
    $money = fn ($v) => '₦' . number_format($v, 0);
    $groups = [
        'Business' => [['properties', 'Properties', fn ($v) => $num($v)], ['occupancy', 'Occupancy', fn ($v) => $v === null ? '—' : $v . '%'], ['nights_booked', 'Nights booked', fn ($v) => $num($v)], ['revenue', 'Room revenue', $money], ['owner_expenses', 'Owner expenses', $money], ['company_expenses', 'Company expenses', $money], ['staff_cost', 'Staff cost', $money]],
        'People' => [['hours_worked', 'Hours worked', fn ($v) => $num($v, 1)], ['late_clock_ins', 'Late clock-ins', fn ($v) => $num($v)], ['off_site_clock_ins', 'Off-site clock-ins', fn ($v) => $num($v)], ['missed_shifts', 'Missed shifts', fn ($v) => $num($v)]],
        'Work' => [['tasks_completed', 'Tasks done', fn ($v) => $num($v)], ['avg_task_hours', 'Avg hours / task', fn ($v) => $num($v, 1)], ['tasks_escalated', 'Escalated', fn ($v) => $num($v)], ['repairs_reported', 'Repairs reported', fn ($v) => $num($v)], ['repairs_resolved', 'Repairs fixed', fn ($v) => $num($v)], ['avg_repair_hours', 'Avg hours / repair', fn ($v) => $num($v, 1)]],
        'Security & stock' => [['incidents', 'Incidents', fn ($v) => $num($v)], ['serious_incidents', 'High / critical', fn ($v) => $num($v)], ['open_incidents', 'Open now', fn ($v) => $num($v)], ['visitors', 'Visitors', fn ($v) => $num($v)], ['patrol_scans', 'Patrol scans', fn ($v) => $num($v)], ['low_stock_items', 'Low stock now', fn ($v) => $num($v)]],
    ];
    // Highlight the figures that need attention.
    $warn = ['late_clock_ins', 'off_site_clock_ins', 'missed_shifts', 'tasks_escalated', 'serious_incidents', 'open_incidents', 'low_stock_items'];
@endphp
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Site report</h2>
        <p class="text-sm text-gray-500">Every site side by side, {{ $from->format('j M') }} – {{ $to->format('j M Y') }}. Staff cost comes from approved pay runs, split by hours worked.</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-xs text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm">Show</button>
        @can('report.export')
        <a href="{{ route('admin.reports.sites.export', request()->query()) }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 text-sm hover:ring-primary"><i class="fas fa-file-csv mr-1"></i> CSV</a>
        @endcan
    </form>
</div>

@foreach($groups as $heading => $columns)
<div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase sticky left-0 bg-gray-50">{{ $heading }}</th>
                @foreach($columns as [, $label])
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($rows as $row)
            <tr>
                <td class="px-4 py-3 sticky left-0 bg-white whitespace-nowrap">
                    <a href="{{ route('admin.sites.edit', $row['site']) }}" class="font-medium hover:text-primary">{{ $row['site']->name }}</a>
                    <div class="text-xs text-gray-500">{{ $row['site']->city }}</div>
                </td>
                @foreach($columns as [$key, , $format])
                <td class="px-4 py-3 text-right whitespace-nowrap {{ in_array($key, $warn, true) && $row[$key] > 0 ? 'text-orange-700 font-medium' : '' }}">{{ $format($row[$key]) }}</td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ count($columns) + 1 }}" class="px-4 py-6 text-center text-gray-500">No active sites.</td></tr>
            @endforelse
        </tbody>
        @if($rows->count() > 1 && $heading === 'Business')
        <tfoot class="bg-gray-50 font-semibold">
            <tr>
                <td class="px-4 py-3 sticky left-0 bg-gray-50">All sites</td>
                @foreach($columns as [$key, , $format])
                <td class="px-4 py-3 text-right whitespace-nowrap">{{ $key === 'occupancy' ? '' : $format($rows->sum($key)) }}</td>
                @endforeach
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endforeach
@endsection
