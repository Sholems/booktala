@extends('layouts.admin')

@section('title', 'Staff Schedule Calendar')
@section('page_title', 'Staff Schedule Calendar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Staff Schedule Calendar</h2>
            <p class="text-gray-500 text-sm">Monthly view of staff schedules</p>
        </div>
        <a href="{{ route('admin.staff-schedules.index') }}" class="px-5 py-2.5 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-list mr-2"></i>List View
        </a>
    </div>

    <!-- Month Selector -->
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <form method="GET" class="flex items-center gap-4">
            <a href="{{ route('admin.staff-schedules.calendar', ['month' => $month - 1, 'year' => $month == 1 ? $year - 1 : $year]) }}" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="text-lg font-bold text-gray-800">{{ \Carbon\Carbon::createFromDate($year, $month)->format('F Y') }}</span>
            <a href="{{ route('admin.staff-schedules.calendar', ['month' => $month + 1, 'year' => $month == 12 ? $year + 1 : $year]) }}" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
        </form>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-100">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase">
                {{ $day }}
            </div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @php
                $firstDay = \Carbon\Carbon::createFromDate($year, $month, 1)->dayOfWeek;
                $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $today = now()->format('Y-m-d');
            @endphp
            
            @for($i = 0; $i < $firstDay; $i++)
                <div class="min-h-[100px] border-b border-r border-gray-100 bg-gray-50"></div>
            @endfor
            
            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $currentDate = \Carbon\Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                    $daySchedules = collect();
                    foreach ($schedules as $staffId => $staffSchedules) {
                        $daySchedules = $daySchedules->merge($staffSchedules->filter(fn($s) => $s->date == $currentDate));
                    }
                @endphp
                <div class="min-h-[100px] border-b border-r border-gray-100 p-2 {{ $currentDate == $today ? 'bg-blue-50' : '' }}">
                    <div class="text-sm font-medium text-gray-500 mb-1">{{ $day }}</div>
                    @foreach($daySchedules->take(2) as $schedule)
                        <div class="text-xs px-2 py-1 mb-1 rounded bg-primary/10 text-primary truncate">
                            {{ $schedule->staff->user->name ?? 'Unknown' }}: {{ $schedule->shift_type }}
                        </div>
                    @endforeach
                    @if($daySchedules->count() > 2)
                        <div class="text-xs text-gray-500">+{{ $daySchedules->count() - 2 }} more</div>
                    @endif
                </div>
            @endfor
            
            @for($i = $firstDay + $daysInMonth; $i < 42; $i++)
                <div class="min-h-[100px] border-b border-r border-gray-100 bg-gray-50"></div>
            @endfor
        </div>
    </div>
</div>
@endsection