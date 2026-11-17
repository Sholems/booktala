@extends('layouts.admin')
@section('page_title', 'Positions')
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Staff Positions</h2>
    <p class="text-sm text-gray-500">The jobs staff can hold at a site. Supervisors and managers are marked by their level.</p>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        @foreach($positions as $department => $group)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-3 bg-gray-50 font-semibold text-sm">{{ \App\Models\Position::DEPARTMENTS[$department] ?? ucfirst($department) }}</div>
            <ul class="divide-y">
                @foreach($group as $position)
                <li class="px-6 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <span class="font-medium">{{ $position->name }}</span>
                        @if($position->level !== 'staff')<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ $position->levelLabel() }}</span>@endif
                        @if($position->description)<p class="text-xs text-gray-500">{{ $position->description }}</p>@endif
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-500">{{ $position->active_count }} {{ Str::plural('assignment', $position->active_count) }}</span>
                        <a href="{{ route('admin.positions.edit', $position) }}" class="text-primary hover:underline">Edit</a>
                        @if($position->active_count === 0)
                        <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" onsubmit="return confirm('Delete this position?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-6 h-fit">
        <h3 class="text-lg font-bold mb-4">Add a position</h3>
        @include('admin.positions._fields', ['position' => new \App\Models\Position(['level' => 'staff']), 'action' => route('admin.positions.store'), 'method' => null, 'submit' => 'Add position'])
    </div>
</div>
@endsection
