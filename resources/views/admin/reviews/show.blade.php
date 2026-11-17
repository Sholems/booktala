@extends('layouts.admin')

@section('page_title', 'Review Details')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="text-2xl font-bold">Review Details</h2>
    <a href="{{ route('admin.reviews.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
        <i class="fas fa-arrow-left mr-2"></i>Back
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold text-lg">
                    {{ substr($review->user->name, 0, 1) }}
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-lg">{{ $review->user->name }}</h3>
                    <p class="text-gray-500 text-sm">{{ $review->created_at->format('M d, Y') }}</p>
                </div>
                <div>
                    <span class="px-2 py-1 text-xs rounded-full {{ $review->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($review->status) }}
                    </span>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-gray-600 mb-2">Rating</p>
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star text-lg {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                    @endfor
                    <span class="ml-2 font-bold">{{ $review->rating }}/5</span>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-gray-600 mb-2">Comment</p>
                <p class="text-gray-800">{{ $review->comment }}</p>
            </div>

            @if($review->admin_reply)
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <p class="text-gray-600 text-sm mb-2">Your Reply</p>
                <p class="text-gray-800">{{ $review->admin_reply }}</p>
            </div>
            @else
            <div class="mb-6">
                <p class="text-gray-600 text-sm mb-2">Reply to this review</p>
                <form action="{{ route('admin.reviews.reply', $review->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <textarea name="reply" rows="3" class="w-full border rounded-lg p-3" placeholder="Write a reply...">{{ old('reply', $review->admin_reply) }}</textarea>
                    <button type="submit" class="mt-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                        {{ $review->admin_reply ? 'Update Reply' : 'Add Reply' }}
                    </button>
                </form>
            </div>
            @endif

            @if($review->status !== 'approved')
            <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Approve Review
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Property</h3>
            <a href="{{ route('admin.properties.show', $review->property->id) }}" class="block">
                <p class="font-semibold text-primary hover:underline">{{ $review->property->title }}</p>
                <p class="text-gray-500 text-sm">{{ $review->property->city }}, {{ $review->property->state }}</p>
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-bold mb-4">Guest</h3>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                    {{ substr($review->user->name, 0, 1) }}
                </div>
                <div>
                    <p class="font-semibold">{{ $review->user->name }}</p>
                    <p class="text-gray-500 text-sm">{{ $review->user->email }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Delete Review
                </button>
            </form>
        </div>
    </div>
</div>
@endsection