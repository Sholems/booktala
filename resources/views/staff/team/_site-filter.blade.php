@if($sites->count() > 1)
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ request()->fullUrlWithQuery(['site' => null]) }}" class="px-3 py-1.5 rounded-full text-sm {{ !$selectedSite ? 'bg-primary text-white' : 'bg-white ring-1 ring-gray-200 text-gray-700 hover:ring-primary' }}">All my sites</a>
    @foreach($sites as $site)
    <a href="{{ request()->fullUrlWithQuery(['site' => $site->id]) }}" class="px-3 py-1.5 rounded-full text-sm {{ $selectedSite === $site->id ? 'bg-primary text-white' : 'bg-white ring-1 ring-gray-200 text-gray-700 hover:ring-primary' }}">{{ $site->name }}</a>
    @endforeach
</div>
@endif
