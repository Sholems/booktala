@extends('layouts.admin')

@section('title', 'Create Event Space')
@section('page_title', 'Create Event Space')

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('admin.event-spaces.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity *</label>
                        <input type="number" name="capacity" required min="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Size (sq ft)</label>
                        <input type="number" name="size"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate (₦)</label>
                        <input type="number" name="hourly_rate" min="0" step="0.01"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Daily Rate (₦)</label>
                        <input type="number" name="daily_rate" min="0" step="0.01"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#E11D2E]">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.event-spaces.index') }}" class="px-6 py-3 border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-[#0B2D5B] text-white font-bold rounded-lg hover:bg-[#123E7A]">
                Create Space
            </button>
        </div>
    </form>
</div>
@endsection