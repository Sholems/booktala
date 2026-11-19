@extends('layouts.admin')
@section('page_title', 'Customer')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> All customers</a>
    <h2 class="mt-1 text-2xl font-bold">{{ $user->name }}</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Details</h3>
        <dl class="text-sm divide-y">
            <div class="py-2 flex justify-between gap-3"><dt class="text-gray-500">Email</dt><dd>{{ $user->email }}</dd></div>
            <div class="py-2 flex justify-between gap-3"><dt class="text-gray-500">Phone</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
            <div class="py-2 flex justify-between gap-3"><dt class="text-gray-500">Joined</dt><dd>{{ $user->created_at?->format('j M Y') }}</dd></div>
            <div class="py-2 flex justify-between gap-3"><dt class="text-gray-500">Bookings</dt><dd>{{ $user->reservations_count }}</dd></div>
        </dl>

        @can('customer.manage')
        <form method="POST" action="{{ route('admin.customers.status', $user) }}" class="mt-5 flex items-center gap-2">
            @csrf @method('PATCH')
            <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="active" @selected($user->status === 'active')>Active</option>
                <option value="suspended" @selected($user->status === 'suspended')>Suspended</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">Update status</button>
        </form>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">ID verification</h3>
        @if($user->id_document)
        <p class="text-sm mb-4">
            ID uploaded ·
            @if($user->id_verified)
            <span class="text-green-700 font-medium">Verified</span>
            @else
            <span class="text-yellow-700 font-medium">Awaiting verification</span>
            @endif
        </p>
        @can('customer.manage')
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.customers.id-document', $user) }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-300 text-sm hover:ring-primary"><i class="fas fa-id-card mr-1"></i> View ID</a>
            @unless($user->id_verified)
            <form method="POST" action="{{ route('admin.customers.verify', $user) }}" onsubmit="return confirm('Mark this ID as verified?')">
                @csrf @method('PATCH')
                <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">Mark verified</button>
            </form>
            @endunless
        </div>
        <p class="mt-3 text-xs text-gray-500">The document is stored privately and only opens for staff who can manage customers.</p>
        @endcan
        @else
        <p class="text-sm text-gray-500">No ID uploaded yet.</p>
        @endif
    </div>
</div>
@endsection
