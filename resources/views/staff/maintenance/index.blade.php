@extends('layouts.staff')

@section('page_title', 'Maintenance Reports')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-semibold">Maintenance Reports</h1>
        
        <div class="mb-6 p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-xl font-semibold">Create New Report</h2>
            <form action="{{ route('staff.maintenance.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-medium">Property</label>
                        <select name="property_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Property</option>
                            @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium">Priority</label>
                        <select name="priority" class="w-full p-2 border rounded" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium">Title</label>
                        <input type="text" name="title" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium">Description</label>
                        <textarea name="description" rows="3" class="w-full p-2 border rounded" required></textarea>
                    </div>
                </div>
                <button type="submit" class="mt-4 px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Submit Report</button>
            </form>
        </div>
        
        @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        @endif

        <h2 class="mb-3 text-xl font-semibold">Assigned to me</h2>
        <div class="mb-8 space-y-4">
            @forelse($myReports as $report)
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex flex-col md:flex-row md:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-1 text-xs rounded-full {{ $report->isUrgent() ? 'bg-red-100 text-red-800' : ($report->priority === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">{{ ucfirst($report->priority) }}</span>
                            <span class="text-xs text-gray-500">{{ \App\Models\MaintenanceReport::STATUSES[$report->status] ?? $report->status }}</span>
                            <h3 class="font-semibold">{{ $report->title }}</h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $report->property->title ?? 'N/A' }} · reported {{ $report->created_at->diffForHumans() }}</p>
                        <p class="mt-2 text-sm text-gray-700">{{ $report->description }}</p>
                    </div>
                    <div class="md:w-72 space-y-2">
                        @if($report->status === 'pending')
                        <form method="POST" action="{{ route('staff.maintenance.update', $report) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="in_progress">
                            <button class="w-full px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Start work</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('staff.maintenance.update', $report) }}" class="space-y-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="resolved">
                            <textarea name="resolution_notes" rows="2" required maxlength="2000" class="w-full border rounded px-2 py-1.5 text-sm" placeholder="What did you fix?"></textarea>
                            <button class="w-full px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">Mark resolved</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-lg shadow p-6 text-gray-500">Nothing assigned to you.</div>
            @endforelse
        </div>

        <h2 class="mb-3 text-xl font-semibold">Other open reports</h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reports as $report)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $report->title }}</td>
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
                        <td class="px-6 py-4 text-gray-500">{{ $report->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No maintenance reports</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection