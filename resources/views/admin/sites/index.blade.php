@extends('layouts.admin')
@section('page_title', 'Sites')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-2xl font-bold">Sites</h2>
        <p class="text-sm text-gray-500">Buildings, estates and standalone apartments. Staff are assigned to a site and see only its properties.</p>
    </div>
    <a href="{{ route('admin.sites.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
        <i class="fas fa-plus mr-2"></i>Add Site
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Properties</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff on site</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($sites as $site)
            <tr class="{{ $site->is_active ? '' : 'opacity-60' }}">
                <td class="px-6 py-4" data-label="Site">
                    <div class="font-medium">{{ $site->name }}</div>
                    @unless($site->is_active)<span class="text-xs text-gray-500">Inactive</span>@endunless
                </td>
                <td class="px-6 py-4 text-sm" data-label="Location">{{ $site->address }}<br><span class="text-gray-500">{{ $site->city }}, {{ $site->state }}</span></td>
                <td class="px-6 py-4" data-label="Properties">{{ $site->properties_count }}</td>
                <td class="px-6 py-4" data-label="Staff on site">
                    @if($site->active_assignments_count)
                        {{ $site->active_assignments_count }}
                    @else
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">No staff</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap" data-label="Actions">
                    <a href="{{ route('admin.sites.edit', $site) }}" class="text-primary hover:underline">Manage</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No sites yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $sites->links() }}</div>
@endsection
