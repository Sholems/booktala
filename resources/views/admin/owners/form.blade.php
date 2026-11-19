@extends('layouts.admin')
@section('page_title', $owner->exists ? 'Edit Owner' : 'Add Owner')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.owners.index') }}" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-arrow-left mr-1"></i> Back to owners</a>
    <h2 class="mt-1 text-2xl font-bold">{{ $owner->exists ? $owner->name : 'Add Owner' }}</h2>
</div>

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ $owner->exists ? route('admin.owners.update', $owner) : route('admin.owners.store') }}" class="space-y-4">
            @csrf
            @if($owner->exists) @method('PUT') @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $owner->name) }}" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="individual" @selected(old('type', $owner->type) === 'individual')>Individual landlord</option>
                        <option value="company" @selected(old('type', $owner->type) === 'company')>Company</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID / TIN <span class="text-xs text-gray-400 font-normal">(FIRS / State IRS)</span></label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $owner->tax_id) }}" placeholder="e.g. 12345678-0001" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $owner->email) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $owner->phone) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $owner->address) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Agreement terms, bank details reference, key contacts...">{{ old('notes', $owner->notes) }}</textarea>
            </div>
            <fieldset class="rounded-lg border border-gray-200 p-4 space-y-3">
                <legend class="px-1 text-sm font-medium text-gray-700">Management agreement</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Commission (% of room revenue)</label>
                        <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="{{ old('commission_rate', $owner->commission_rate ?? 20) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm sm:mt-6">
                        <input type="hidden" name="deduct_expenses" value="0">
                        <input type="checkbox" name="deduct_expenses" value="1" @checked(old('deduct_expenses', $owner->deduct_expenses ?? true))> Deduct owner expenses from payouts
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bank</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $owner->bank_name) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Account name</label>
                        <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $owner->bank_account_name) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Account number</label>
                        <input type="text" inputmode="numeric" maxlength="10" name="bank_account_number" value="{{ old('bank_account_number', $owner->bank_account_number) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </fieldset>
            @unless($owner->is_company_owned)
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $owner->is_active))> Active
            </label>
            @else
            <input type="hidden" name="is_active" value="1">
            <p class="text-xs text-gray-500">This is the company's own portfolio. New properties belong to it unless you choose another owner.</p>
            @endunless
            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">{{ $owner->exists ? 'Save owner' : 'Add owner' }}</button>
        </form>
    </div>

    @if($owner->exists)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-1">Properties</h3>
        <p class="text-sm text-gray-500 mb-4">Change a property's owner from its edit page.</p>
        <ul class="divide-y">
            @forelse($owner->properties as $property)
            <li class="py-2 flex justify-between gap-3 text-sm">
                <a href="{{ route('admin.properties.edit', $property) }}" class="text-primary hover:underline">{{ $property->title }}</a>
                <span class="text-gray-500">{{ $property->site->name ?? 'No site' }}</span>
            </li>
            @empty
            <li class="py-2 text-sm text-gray-500">No properties yet.</li>
            @endforelse
        </ul>

        @unless($owner->is_company_owned)
        <div class="mt-6 border-t pt-5">
            <h3 class="text-lg font-bold mb-1">Owner portal</h3>
            @php $portalUser = $owner->user; @endphp
            @if($portalUser)
            <p class="text-sm text-gray-600 mb-3">
                Login: <span class="font-medium">{{ $portalUser->email }}</span>
                <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $portalUser->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($portalUser->status) }}</span>
            </p>
            @else
            <p class="text-sm text-gray-500 mb-3">Give {{ $owner->name }} a login to see occupancy, upcoming stays and approved statements. They see only their own properties.</p>
            @endif
            @if($errors->hasAny(['email', 'password']))
            <div class="mb-3 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                @foreach($errors->only(['email', 'password']) as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif
            <form method="POST" action="{{ route('admin.owners.portal', $owner) }}" class="space-y-3" autocomplete="off">
                @csrf
                <input type="email" name="email" required value="{{ old('email', $portalUser->email ?? $owner->email) }}" placeholder="Login email" class="w-full border rounded-lg px-3 py-2 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="{{ $portalUser ? 'New password' : 'Password' }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Confirm password" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90">{{ $portalUser ? 'Update login' : 'Create login' }}</button>
            </form>
            @if($portalUser && $portalUser->status === 'active')
            <form method="POST" action="{{ route('admin.owners.portal.revoke', $owner) }}" class="mt-2" onsubmit="return confirm('Suspend portal access?')">
                @csrf @method('PATCH')
                <button type="submit" class="text-sm text-red-600 hover:underline">Suspend access</button>
            </form>
            @endif
        </div>
        @endunless

        @can('finance.manage')
        @if($owner->statements->isNotEmpty())
        <div class="mt-6 border-t pt-5">
            <h3 class="text-lg font-bold mb-2">Statements</h3>
            <ul class="divide-y text-sm">
                @foreach($owner->statements->take(12) as $statement)
                <li class="py-2 flex justify-between gap-3">
                    <a href="{{ route('admin.statements.show', $statement) }}" class="text-primary hover:underline">{{ $statement->periodLabel() }}</a>
                    <span>₦{{ number_format($statement->net_payout, 2) }} · {{ \App\Models\OwnerStatement::STATUSES[$statement->status] }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @endcan

        @unless($owner->is_company_owned || $owner->properties->isNotEmpty())
        <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}" class="mt-6" onsubmit="return confirm('Delete this owner?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-red-600 text-sm hover:underline">Delete owner</button>
        </form>
        @endunless
    </div>
    @endif
</div>
@endsection
