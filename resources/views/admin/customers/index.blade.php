@extends('layouts.admin')
@section('page_title', 'Customers')
@section('content')
<h2 class="text-2xl font-bold mb-6">All Customers</h2>
<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
<table class="min-w-full mobile-card-table">
<thead class="bg-gray-50">
<tr>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verified</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200">
@forelse($customers as $customer)
<tr>
<td class="px-6 py-4" data-label="Name">{{ $customer->name }}</td>
<td class="px-6 py-4" data-label="Email">{{ $customer->email }}</td>
<td class="px-6 py-4" data-label="Phone">{{ $customer->phone }}</td>
<td class="px-6 py-4" data-label="Status">{{ ucfirst($customer->status) }}</td>
<td class="px-6 py-4" data-label="Verified">{{ $customer->id_verified ? 'Yes' : 'No' }}</td>
</tr>
@empty
<tr>
<td colspan="5" class="px-6 py-4 text-center">No customers</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection