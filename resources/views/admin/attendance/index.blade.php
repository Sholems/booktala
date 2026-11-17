@extends('layouts.admin')
@section('page_title', 'Attendance')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Attendance</h2>
        <p class="text-sm text-gray-500">Clock-ins across all sites, {{ $from->format('j M') }} – {{ $to->format('j M Y') }}. Export for payroll.</p>
    </div>
    <a href="{{ route('admin.attendance.export', request()->query()) }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90"><i class="fas fa-file-csv mr-2"></i>Export CSV</a>
</div>

<form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Site</label>
        <select name="site_id" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="">All sites</option>
            @foreach($sites as $site)
            <option value="{{ $site->id }}" @selected((int) request('site_id') === $site->id)>{{ $site->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Staff</label>
        <select name="staff_id" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="">Everyone</option>
            @foreach($staffList as $member)
            <option value="{{ $member->id }}" @selected((int) request('staff_id') === $member->id)>{{ $member->user->name ?? $member->employee_id }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">From</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">To</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Filter</button>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive mb-6">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shifts worked</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Off-site clock-ins</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Forgot to clock out</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($summary as $row)
            <tr>
                <td class="px-4 py-3 font-medium" data-label="Staff">{{ $row['staff']->user->name ?? 'Staff' }}</td>
                <td class="px-4 py-3" data-label="Shifts worked">{{ $row['shifts'] }}</td>
                <td class="px-4 py-3" data-label="Hours">{{ number_format($row['minutes'] / 60, 1) }}</td>
                <td class="px-4 py-3 {{ $row['late'] ? 'text-orange-600 font-medium' : '' }}" data-label="Late">{{ $row['late'] }}</td>
                <td class="px-4 py-3 {{ $row['off_site'] ? 'text-orange-600 font-medium' : '' }}" data-label="Off-site clock-ins">{{ $row['off_site'] }}</td>
                <td class="px-4 py-3 {{ $row['auto_closed'] ? 'text-orange-600 font-medium' : '' }}" data-label="Forgot to clock out">{{ $row['auto_closed'] }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No clock-ins in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h3 class="text-lg font-bold mb-3">All clock-ins</h3>
@include('partials.attendance-table', ['records' => $records, 'showSite' => true])
@endsection
