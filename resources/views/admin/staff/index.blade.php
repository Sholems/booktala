@extends('layouts.admin')
@section('page_title', 'Staff')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="text-2xl font-bold">All Staff</h2>
    <button onclick="document.getElementById('createStaffModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
        <i class="fas fa-plus mr-2"></i>Add Staff
    </button>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<!-- Create Staff Modal -->
<div id="createStaffModal" class="{{ $errors->any() ? '' : 'hidden' }} fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Add New Staff</h3>
                <button onclick="document.getElementById('createStaffModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <select name="position_id" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            @foreach($positions->groupBy('department') as $department => $group)
                            <optgroup label="{{ \App\Models\Position::DEPARTMENTS[$department] ?? ucfirst($department) }}">
                                @foreach($group as $position)
                                <option value="{{ $position->id }}" @selected((int) old('position_id') === $position->id)>{{ $position->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select name="site_id" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Where will they work?</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) old('site_id') === $site->id)>{{ $site->name }} — {{ $site->city }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">They will only see this site's guests and work. Add more sites from their profile.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required minlength="8" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Create Staff</button>
                    <button type="button" onclick="document.getElementById('createStaffModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sites</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($staff as $user)
            <tr>
                <td class="px-6 py-4" data-label="Name">{{ $user->name }}</td>
                <td class="px-6 py-4" data-label="Email">{{ $user->email }}<br><span class="text-sm text-gray-500">{{ $user->phone }}</span></td>
                <td class="px-6 py-4 text-sm" data-label="Position">{{ $user->staff?->primaryAssignment?->position->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm" data-label="Sites">
                    @php $memberSites = $user->staff?->activeAssignments->pluck('site.name')->unique() ?? collect(); @endphp
                    @if($memberSites->isEmpty())
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Not assigned</span>
                    @else
                        {{ $memberSites->take(2)->join(', ') }}@if($memberSites->count() > 2) <span class="text-gray-500">+{{ $memberSites->count() - 2 }} more</span>@endif
                    @endif
                </td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap" data-label="Actions">
                    <a href="{{ route('admin.staff.edit', $user) }}" class="text-primary hover:underline mr-3">Edit / Reset password</a>
                    <form action="{{ route('admin.staff.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this staff?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No staff found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $staff->links() }}</div>
@endsection