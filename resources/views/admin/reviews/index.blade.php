@extends('layouts.admin')
@section('page_title', 'Reviews')
@section('content')
<h2 class="text-2xl font-bold mb-6">All Reviews</h2>
<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($reviews as $review)
            <tr>
                <td class="px-6 py-4" data-label="Property">{{ $review->property->title ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Guest">{{ $review->user->name ?? 'N/A' }}</td>
                <td class="px-6 py-4" data-label="Rating">
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)<i class="fas fa-star text-xs {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>@endfor
                    </div>
                </td>
                <td class="px-6 py-4" data-label="Comment">{{ Str::limit($review->comment, 50) }}</td>
                <td class="px-6 py-4" data-label="Status">
                    <span class="px-2 py-1 text-xs rounded-full {{ $review->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($review->status) }}
                    </span>
                </td>
                <td class="px-6 py-4" data-label="Actions">
                    <a href="{{ route('admin.reviews.show', $review->id) }}" class="text-primary hover:underline mr-3">View</a>
                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this review?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No reviews</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
