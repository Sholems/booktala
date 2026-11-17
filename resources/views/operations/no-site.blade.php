@extends($layout)
@section('page_title', $title)
@section('content')
<div class="max-w-xl bg-white rounded-lg shadow p-6 text-gray-600">
    <i class="fas fa-info-circle mr-1"></i>
    @if($p === 'admin')
    There are no active sites yet. <a href="{{ route('admin.sites.create') }}" class="text-primary hover:underline">Add a site</a> first.
    @else
    You are not assigned to a site yet. Ask your supervisor or head office.
    @endif
</div>
@endsection
