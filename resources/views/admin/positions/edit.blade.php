@extends('layouts.admin')
@section('page_title', 'Edit Position')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.positions.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Back to positions</a>
    <h2 class="mt-1 text-2xl font-bold">{{ $position->name }}</h2>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="bg-white rounded-lg shadow p-6 max-w-lg">
    @include('admin.positions._fields', ['action' => route('admin.positions.update', $position), 'method' => 'PUT', 'submit' => 'Save position'])
</div>
@endsection
