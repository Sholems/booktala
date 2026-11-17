{{-- Who owns the property and which site (building/estate or standalone) it belongs to. --}}
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium mb-2">Owner</label>
        <select name="owner_id" class="w-full border rounded px-3 py-2">
            @foreach($owners as $owner)
            <option value="{{ $owner->id }}" @selected((int) old('owner_id', $property?->owner_id ?? ($owner->is_company_owned ? $owner->id : null)) === $owner->id)>
                {{ $owner->name }}{{ $owner->is_company_owned ? ' (company)' : '' }}
            </option>
            @endforeach
        </select>
        @can('owner.manage')
        <p class="mt-1 text-xs text-gray-500">Landlord not listed? <a href="{{ route('admin.owners.create') }}" class="text-primary hover:underline" target="_blank">Add an owner</a>.</p>
        @endcan
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">Site</label>
        <select name="site_id" class="w-full border rounded px-3 py-2">
            <option value="">{{ $property ? 'Keep current site' : 'New site at this address (standalone)' }}</option>
            @foreach($sites as $site)
            <option value="{{ $site->id }}" @selected((int) old('site_id', $property?->site_id) === $site->id)>{{ $site->name }} — {{ $site->city }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Staff assigned to the site work on every property in it.</p>
    </div>
</div>
