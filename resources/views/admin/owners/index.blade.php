@extends('layouts.admin')
@section('page_title', 'Owners')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-2xl font-bold">Property Owners</h2>
        <p class="text-sm text-gray-500">G-Briel's own portfolio and the landlords whose properties it manages.</p>
    </div>
    <a href="{{ route('admin.owners.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
        <i class="fas fa-plus mr-2"></i>Add Owner
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Properties</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($owners as $owner)
            <tr>
                <td class="px-6 py-4" data-label="Owner">
                    <div class="font-medium">{{ $owner->name }}</div>
                    <div class="text-xs text-gray-500">
                        {{ $owner->type === 'company' ? 'Company' : 'Individual' }}
                        @if($owner->is_company_owned)<span class="ml-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary">Our portfolio</span>@endif
                    </div>
                </td>
                <td class="px-6 py-4 text-sm" data-label="Contact">
                    {{ $owner->email ?? '—' }}<br><span class="text-gray-500">{{ $owner->phone }}</span>
                </td>
                <td class="px-6 py-4" data-label="Properties">{{ $owner->properties_count }}</td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full {{ $owner->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $owner->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap" data-label="Actions">
                    <a href="{{ route('admin.owners.edit', $owner) }}" class="text-primary hover:underline">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No owners yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $owners->links() }}</div>
@endsection
