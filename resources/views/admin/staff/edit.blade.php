@extends('layouts.admin')
@section('page_title', 'Edit Staff')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Back to staff</a>
        <h2 class="mt-1 text-2xl font-bold">{{ $member->name }}</h2>
    </div>
    <span class="px-3 py-1 text-sm rounded-full {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
        {{ ucfirst($member->status) }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-1">Staff details</h3>
        <p class="text-sm text-gray-500 mb-5">Update contact details or suspend access. Suspended staff cannot log in.</p>

        @if($errors->hasAny(['name', 'email', 'phone', 'status']))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            @foreach($errors->only(['name', 'email', 'phone', 'status']) as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.staff.update', $member) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $member->name) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $member->email) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $member->phone) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="active" @selected(old('status', $member->status) === 'active')>Active</option>
                    <option value="suspended" @selected(old('status', $member->status) === 'suspended')>Suspended</option>
                </select>
            </div>
            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">Save details</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-1">Reset password</h3>
        <p class="text-sm text-gray-500 mb-5">Set a new password for this staff member. They will be signed out of any open sessions.</p>

        @if($errors->has('password'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            @foreach($errors->get('password') as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.staff.password', $member) }}" class="space-y-4" autocomplete="off">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500">At least 8 characters.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <button type="submit" class="bg-accent text-white px-5 py-2 rounded-lg hover:bg-accent/90" onclick="return confirm('Reset this staff member\'s password?')">Reset password</button>
        </form>
    </div>
</div>

@php $assignments = $member->staff?->assignments ?? collect(); @endphp
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-1">Sites &amp; positions</h3>
    <p class="text-sm text-gray-500 mb-5">What {{ $member->name }} does and where. They only see guests, tasks and reports for the sites they are currently assigned to.</p>

    @if($errors->hasAny(['site_id', 'position_id', 'starts_on']))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
        @foreach($errors->only(['site_id', 'position_id', 'starts_on']) as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="overflow-x-auto mb-6">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($assignments as $assignment)
                @php $current = $assignment->isActive(); $upcoming = $assignment->starts_on->isFuture(); @endphp
                <tr class="{{ $current || $upcoming ? '' : 'text-gray-400' }}">
                    <td class="px-4 py-2">
                        @can('site.manage')<a href="{{ route('admin.sites.edit', $assignment->site) }}" class="hover:underline">{{ $assignment->site->name }}</a>@else{{ $assignment->site->name }}@endcan
                        @if($assignment->is_primary)<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">Primary</span>@endif
                    </td>
                    <td class="px-4 py-2">{{ $assignment->position->name }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ $assignment->starts_on->format('M j, Y') }} – {{ $assignment->ends_on?->format('M j, Y') ?? 'now' }}
                        @if($upcoming)<span class="ml-1 text-xs text-blue-600">starts later</span>@elseif(!$current)<span class="ml-1 text-xs">ended</span>@endif
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-right">
                        @if($current && !$assignment->is_primary)
                        <form method="POST" action="{{ route('admin.staff.assignments.primary', [$member, $assignment]) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-primary hover:underline mr-3">Make primary</button>
                        </form>
                        @endif
                        @if($current || $upcoming)
                        <form method="POST" action="{{ route('admin.staff.assignments.end', [$member, $assignment]) }}" class="inline" onsubmit="return confirm('End this assignment? {{ $member->name }} will stop seeing {{ addslashes($assignment->site->name) }}.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-red-600 hover:underline">End</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-3 text-yellow-700">Not assigned to any site, so this staff member sees no guests or work. Add an assignment below.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.staff.assignments.store', $member) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
            <select name="site_id" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Choose a site…</option>
                @foreach($sites as $site)
                <option value="{{ $site->id }}" @selected((int) old('site_id') === $site->id)>{{ $site->name }} — {{ $site->city }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <select name="position_id" required class="w-full border rounded-lg px-3 py-2">
                @foreach($positions->groupBy('department') as $department => $group)
                <optgroup label="{{ \App\Models\Position::DEPARTMENTS[$department] ?? ucfirst($department) }}">
                    @foreach($group as $position)
                    <option value="{{ $position->id }}" @selected((int) old('position_id', $member->staff?->primaryAssignment?->position_id) === $position->id)>{{ $position->name }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Starts</label>
            <input type="date" name="starts_on" value="{{ old('starts_on', today()->toDateString()) }}" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="flex items-center gap-2 text-sm mb-2"><input type="checkbox" name="is_primary" value="1"> Primary post</label>
            <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">Assign</button>
        </div>
    </form>
</div>

@can('payroll.manage')
@if($member->staff)
@include('admin.staff._pay', ['staff' => $member->staff->load(['salaryStructure', 'deductions'])])
@endif
@endcan
@endsection
