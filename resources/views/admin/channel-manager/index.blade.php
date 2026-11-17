@extends('layouts.admin')
@php
$isHotelMode = \App\Models\SystemSetting::get('hotel_mode', false);
$channelsList = [
    'bookingcom' => ['name' => 'Booking.com', 'desc' => 'Connect to the world\'s largest online accommodation network'],
    'expedia' => ['desc' => 'Access millions of travelers through Expedia'],
    'airbnb' => ['desc' => 'Sync with Airbnb for short-term rental bookings'],
];
@endphp
@section('page_title', 'Channel Manager')
@section('content')
<div class="mb-6">
    <p class="text-gray-600">Connect your properties to online travel agencies (OTAs) to increase your reach and bookings.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    @foreach($channelsList as $key => $channel)
    @php $channelData = $channels[$key] ?? null; @endphp
    <div class="bg-white rounded-lg shadow p-6 {{ $channelData && $channelData->is_enabled ? 'ring-2 ring-green-500' : '' }}">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                <i class="fas fa-{{ $key === 'bookingcom' ? 'bed' : ($key === 'expedia' ? 'plane' : 'home') }} text-primary text-xl"></i>
            </div>
            @if($channelData)
            <span class="px-3 py-1 text-xs rounded-full {{ $channelData->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $channelData->is_enabled ? 'Connected' : 'Disconnected' }}
            </span>
            @endif
        </div>
        <h3 class="text-lg font-bold mb-2">{{ ucfirst($key) }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $channel['desc'] }}</p>
        
        @if($channelData && $channelData->last_sync)
        <p class="text-xs text-gray-500 mb-4">Last sync: {{ $channelData->last_sync->diffForHumans() }}</p>
        @endif

        @if($channelData)
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.channel-manager.toggle', $channelData) }}" class="flex-1">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full px-4 py-2 text-sm border rounded hover:bg-gray-50">
                    {{ $channelData->is_enabled ? 'Disable' : 'Enable' }}
                </button>
            </form>
            @if($channelData->is_enabled)
            <form method="POST" action="{{ route('admin.channel-manager.sync', $channelData) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm bg-primary text-white rounded hover:bg-primary/90">
                    <i class="fas fa-sync"></i>
                </button>
            </form>
            @endif
        </div>
        @endif

        <button onclick="document.getElementById('modal-{{ $key }}').showModal()" class="mt-3 w-full px-4 py-2 text-sm bg-accent text-white rounded hover:bg-accent/90">
            {{ $channelData ? 'Configure' : 'Connect' }}
        </button>

        <dialog id="modal-{{ $key }}" class="modal p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Configure {{ ucfirst($key) }}</h3>
            <form method="POST" action="{{ route('admin.channel-manager.store') }}">
                @csrf
                <input type="hidden" name="channel_name" value="{{ $key }}">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">API Key</label>
                    <input type="text" name="api_key" value="{{ $channelData->api_key ?? '' }}" class="w-full border rounded px-3 py-2" placeholder="Enter API key">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">API Secret</label>
                    <input type="password" name="api_secret" value="{{ $channelData->api_secret ?? '' }}" class="w-full border rounded px-3 py-2" placeholder="Enter API secret">
                </div>
                @if(!$isHotelMode)
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Property</label>
                    <select name="property_id" class="w-full border rounded px-3 py-2">
                        <option value="">Select property</option>
                        @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ $channelData && $channelData->property_id == $property->id ? 'selected' : '' }}>
                            {{ $property->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Sync Frequency</label>
                    <select name="sync_frequency" class="w-full border rounded px-3 py-2">
                        <option value="realtime" {{ $channelData && $channelData->sync_frequency == 'realtime' ? 'selected' : '' }}>Real-time</option>
                        <option value="hourly" {{ $channelData && $channelData->sync_frequency == 'hourly' ? 'selected' : '' }}>Hourly</option>
                        <option value="daily" {{ $channelData && $channelData->sync_frequency == 'daily' ? 'selected' : '' }}>Daily</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-{{ $key }}').close()" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Save</button>
                </div>
            </form>
        </dialog>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold mb-4">Sync Status</h3>
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Channel</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Status</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Last Sync</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Frequency</th>
            </tr>
        </thead>
        <tbody>
            @forelse($channels as $channel)
            <tr class="border-t">
                <td class="px-4 py-3 capitalize">{{ $channel->channel_name }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full {{ $channel->is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $channel->is_enabled ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ $channel->last_sync ? $channel->last_sync->format('M d, Y H:i') : 'Never' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 capitalize">{{ $channel->sync_frequency }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-3 text-center text-gray-500">No channels configured</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection