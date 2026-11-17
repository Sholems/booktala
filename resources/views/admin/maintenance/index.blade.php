@extends('layouts.admin')
@section('page_title', 'Maintenance')
@section('content')
<h2 class="text-2xl font-bold mb-6">Maintenance Reports</h2>
@if($errors->any())
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
@endif

<div class="mb-6 flex justify-between items-center">
    <button onclick="document.getElementById('createMaintenanceModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i> Create Report
    </button>
</div>

<div id="createMaintenanceModal" class="hidden mb-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Create Maintenance Report</h3>
    <form method="POST" action="{{ route('admin.maintenance.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Title</label>
                <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Property</label>
                <select name="property_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select Property</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Priority</label>
                <select name="priority" class="w-full border rounded px-3 py-2">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Assign to</label>
                <select name="assigned_to" class="w-full border rounded px-3 py-2">
                    <option value="">Unassigned</option>
                    @foreach($staff as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2" rows="3" required></textarea>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Create Report</button>
            <button type="button" onclick="document.getElementById('createMaintenanceModal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
        </div>
    </form>
</div>

<div class="mb-4 inline-flex rounded-lg border bg-white p-1 text-sm">
    @foreach(['open' => 'Open', 'resolved' => 'Resolved', 'all' => 'All'] as $key => $label)
    <a href="{{ route('admin.maintenance.index', ['show' => $key]) }}" class="px-4 py-1.5 rounded {{ $filter === $key ? 'bg-primary text-white' : 'text-gray-600 hover:text-primary' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="space-y-4">
    @forelse($reports as $report)
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-col lg:flex-row lg:justify-between gap-4">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-1 text-xs rounded-full {{ $report->isUrgent() ? 'bg-red-100 text-red-800' : ($report->priority === 'high' ? 'bg-orange-100 text-orange-800' : ($report->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) }}">{{ \App\Models\MaintenanceReport::PRIORITIES[$report->priority] ?? ucfirst($report->priority) }}</span>
                    <span class="px-2 py-1 text-xs rounded-full {{ $report->status === 'resolved' ? 'bg-green-100 text-green-800' : ($report->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">{{ \App\Models\MaintenanceReport::STATUSES[$report->status] ?? $report->status }}</span>
                    <h3 class="font-semibold">{{ $report->title }}</h3>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $report->property->title ?? 'N/A' }} · reported by {{ $report->reportedBy->name ?? 'N/A' }} · {{ $report->created_at->format('M d, Y') }}</p>
                <p class="mt-2 text-sm text-gray-700">{{ $report->description }}</p>
                @if($report->resolution_notes)
                <p class="mt-2 text-sm text-green-800 bg-green-50 rounded p-2"><strong>Fix:</strong> {{ $report->resolution_notes }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.maintenance.update', $report) }}" class="lg:w-80 space-y-2">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-2 gap-2">
                    <select name="assigned_to" class="border rounded px-2 py-1.5 text-sm" aria-label="Assigned to">
                        <option value="">Unassigned</option>
                        @foreach($staff as $member)
                        <option value="{{ $member->id }}" @selected($report->assigned_to === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border rounded px-2 py-1.5 text-sm" aria-label="Status">
                        @foreach(\App\Models\MaintenanceReport::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <textarea name="resolution_notes" rows="2" maxlength="2000" class="w-full border rounded px-2 py-1.5 text-sm" placeholder="Resolution notes (required to resolve)">{{ $report->resolution_notes }}</textarea>
                <button class="w-full bg-primary text-white px-3 py-1.5 rounded text-sm hover:bg-primary/90">Save</button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">No maintenance reports</div>
    @endforelse
</div>
<div class="mt-4">{{ $reports->links() }}</div>
@endsection
