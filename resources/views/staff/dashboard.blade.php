@extends('layouts.staff')
@php use Illuminate\Support\Str; @endphp

@section('page_title', 'Dashboard')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
                <p class="text-sm text-gray-500">{{ now()->format('l, j F Y') }}</p>
            </div>
            <a href="{{ route('staff.schedule.index') }}" class="text-sm text-gray-600 hover:text-primary">
                <i class="fas fa-calendar-alt mr-1"></i>
                @if($todayShift)
                On shift today: {{ $todayShift->label() }}@if($todayShift->site) at {{ $todayShift->site->name }}@endif
                @else
                No shift scheduled today
                @endif
            </a>
        </div>

        @include('staff.partials.clock-card')

        @if($isSupervisor)
        <a href="{{ route('staff.team.index') }}" class="mb-6 flex items-center justify-between rounded-lg bg-primary text-white px-4 py-3 hover:bg-primary/90">
            <span><i class="fas fa-users mr-2"></i>Team overview: who is on duty, open and overdue work, roster</span>
            <i class="fas fa-chevron-right"></i>
        </a>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6 md:mb-8">
            @foreach($today as $tile)
            <a href="{{ $tile['url'] }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100 hover:ring-primary/30 hover:shadow transition {{ $tile['count'] === 0 ? 'opacity-60' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-bold {{ $tile['count'] > 0 ? $tile['color'] : 'text-gray-400' }}">{{ $tile['count'] }}</span>
                    <i class="fas {{ $tile['icon'] }} {{ $tile['count'] > 0 ? $tile['color'] : 'text-gray-300' }}"></i>
                </div>
                <div class="mt-1 text-xs text-gray-600">{{ $tile['label'] }}</div>
            </a>
            @endforeach
        </div>

        <div class="mb-6 md:mb-8">
            <h2 class="mb-3 md:mb-4 text-lg md:text-xl font-semibold">My Tasks</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
                <table class="min-w-full divide-y divide-gray-200 mobile-card-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tasks as $task)
                        <tr>
                            <td class="px-6 py-4" data-label="Task">
                                <div class="font-medium text-gray-900">{{ $task->title }}</div>
                                <div class="text-sm text-gray-500">{{ $task->description }}</div>
                            </td>
                            <td class="px-6 py-4" data-label="Property">{{ $task->property->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4" data-label="Due Date">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                            <td class="px-6 py-4" data-label="Status">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $task->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4" data-label="Action">
                                @if($task->status == 'pending')
                                <form method="POST" action="{{ route('staff.tasks.start', $task) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Start</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('staff.tasks.complete', $task) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Complete</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No tasks assigned</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="mb-4 text-xl font-semibold">Today's Check-ins</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($todayCheckIns as $reservation)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $reservation->user->name ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $reservation->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $reservation->property->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $reservation->check_in->format('M d, Y') }}</td>
                            <td class="px-6 py-4">{{ $reservation->check_out->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                @if($reservation->booking_status == 'confirmed')
@include('staff.checkin._action')
                                @elseif($reservation->booking_status == 'checked_in')
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Checked In</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No check-ins today</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-xl font-semibold">Maintenance Reports</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentMaintenances as $report)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $report->title }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($report->description, 50) }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $report->property->title ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $report->isUrgent() ? 'bg-red-100 text-red-800' : 
                                       ($report->priority == 'high' ? 'bg-orange-100 text-orange-800' : 
                                       ($report->priority == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) }}">
                                    {{ ucfirst($report->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $report->status == 'resolved' ? 'bg-green-100 text-green-800' : 
                                       ($report->status == 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($report->status != 'resolved')
                                <button onclick="document.getElementById('report-{{ $report->id }}').classList.remove('hidden')" class="text-xs bg-primary text-white px-3 py-1 rounded">Update</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No maintenance reports</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection