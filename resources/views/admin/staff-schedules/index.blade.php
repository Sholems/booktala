@extends('layouts.admin')

@section('title', 'Staff Schedules')
@section('page_title', 'Staff Schedules')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Staff Schedules</h2>
            <p class="text-gray-500 text-sm">Manage staff working schedules</p>
        </div>
        <a href="{{ route('admin.staff-schedules.calendar') }}" class="px-5 py-2.5 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A] transition">
            <i class="fas fa-calendar-alt mr-2"></i>Calendar View
        </a>
    </div>

    <!-- Date Selector -->
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Select Date:</label>
            <input type="date" name="date" value="{{ $date }}"
                class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
            <select name="site_id" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                <option value="">All sites</option>
                @foreach($sites as $site)
                <option value="{{ $site->id }}" @selected($siteId === $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-5 py-2 bg-[#E11D2E] text-white font-bold rounded-lg hover:bg-[#C21627]">
                View
            </button>
        </form>
    </div>

    <!-- Add Schedule Form -->
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add Schedule</h3>
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif
        <form method="POST" action="{{ route('admin.staff-schedules.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Staff</label>
                <select name="staff_id" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    <option value="">Select Staff</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}">{{ $s->user->name ?? 'Unknown' }} — {{ $s->activeAssignments->pluck('site.name')->unique()->join(', ') ?: 'no site' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                <select name="site_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    <option value="">Their primary site</option>
                    @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected($siteId === $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                <select name="shift_type" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    <option value="morning">Morning (6-2)</option>
                    <option value="afternoon">Afternoon (2-10)</option>
                    <option value="night">Night (10-6)</option>
                    <option value="day">Day (8-6)</option>
                    <option value="night_shift">Night Shift (8-8)</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional notes" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
            </div>
            <button type="submit" class="px-5 py-2 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A]">
                <i class="fas fa-plus mr-2"></i>Add
            </button>
        </form>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Staff</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Shift</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Notes</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($schedules as $schedule)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-[#E11D2E] rounded-full flex items-center justify-center text-white text-sm font-bold">
                                {{ substr($schedule->staff->user->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $schedule->staff->user->name ?? 'Unknown' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ \Carbon\Carbon::parse($schedule->date)->format('M j, Y') }}
                        <div class="text-xs text-gray-500">{{ $schedule->site->name ?? 'No site' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded capitalize">
                            {{ str_replace('_', ' ', $schedule->shift_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $schedule->startsAt()->format('g:i A') }} – {{ $schedule->endsAt()->format('g:i A') }}
                        @if($schedule->attendance)
                        <div class="text-xs {{ $schedule->attendance->isLate() ? 'text-orange-600' : 'text-green-600' }}">
                            In {{ $schedule->attendance->clock_in_at->format('g:i A') }}@if($schedule->attendance->clock_out_at) · out {{ $schedule->attendance->clock_out_at->format('g:i A') }}@endif
                            @if($schedule->attendance->isLate()) · {{ $schedule->attendance->late_minutes }} min late @endif
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('admin.staff-schedules.update-status', $schedule) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="text-xs border-0 bg-transparent focus:ring-0 cursor-pointer
                                @if($schedule->status === 'scheduled') text-yellow-600
                                @elseif($schedule->status === 'completed') text-green-600
                                @elseif($schedule->status === 'cancelled') text-red-600
                                @else text-gray-600 @endif">
                                <option value="scheduled" {{ $schedule->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="completed" {{ $schedule->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $schedule->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="no_show" {{ $schedule->status === 'no_show' ? 'selected' : '' }}>No Show</option>
                            </select>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $schedule->notes ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST" action="{{ route('admin.staff-schedules.destroy', $schedule) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Delete?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No schedules for this date
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection