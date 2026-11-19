@extends('layouts.admin')
@section('page_title', 'Channel Manager & OTA Sync')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Channel Manager & OTA Sync</h2>
        <p class="text-sm text-gray-500">Two-way real-time calendar synchronization with Airbnb, Booking.com, VRBO & Expedia.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('admin.channel-manager.sync-all') }}" onsubmit="return confirm('Initiate synchronization across all configured OTA channels?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
                <i class="fas fa-sync"></i>
                <span>Sync All Feeds</span>
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-lg p-4 text-sm text-rose-800 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-rose-600"></i>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

@if($conflictsCount > 0)
<div class="mb-6 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4 text-sm text-amber-900 shadow-sm">
    <div class="flex items-start gap-3">
        <i class="fas fa-shield-alt text-amber-600 text-lg mt-0.5"></i>
        <div>
            <h4 class="font-bold text-amber-900">Double-Booking Collision Prevention Active</h4>
            <p class="text-amber-800 text-xs mt-0.5">
                {{ $conflictsCount }} channel feed(s) have detected conflicting external bookings that collided with existing reservations or maintenance holds. BookTala safely blocked the double-bookings. Inspect the affected channels below.
            </p>
        </div>
    </div>
</div>
@endif

<!-- Channel Status Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($channelsMeta as $channelKey => $meta)
    @php
        $channelMappings = $mappings->where('channel', $channelKey);
        $activeCount = $channelMappings->where('is_active', true)->count();
        $conflictsInChannel = $channelMappings->where('sync_status', 'conflict')->count();
    @endphp
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $meta['color'] }};">
                <i class="{{ $meta['icon'] }} text-lg"></i>
            </div>
            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $activeCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                {{ $activeCount }} Connected
            </span>
        </div>
        <h3 class="font-bold text-slate-800">{{ $meta['name'] }}</h3>
        <p class="text-xs text-slate-500 mt-1">Default markup buffer: {{ number_format(($meta['default_markup'] - 1) * 100) }}%</p>
        @if($conflictsInChannel > 0)
        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600 mt-2">
            <i class="fas fa-exclamation-triangle"></i> {{ $conflictsInChannel }} conflict(s) blocked
        </span>
        @endif
    </div>
    @endforeach
</div>

<!-- Property Mappings & 2-Way Sync Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6" x-data="{ addModalOpen: false, selectedUnit: '', selectedChannel: 'airbnb', selectedMarkup: '1.15' }">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="font-bold text-slate-800 text-base">Unit Channel Connections</h3>
            <p class="text-xs text-slate-500">Inbound iCal import feeds & outbound export links per unit.</p>
        </div>
        <button @click="addModalOpen = true" type="button" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-3.5 py-1.5 rounded-lg text-xs font-medium shadow-sm transition">
            <i class="fas fa-plus"></i>
            <span>Add Connection</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">Unit / Property</th>
                    <th class="py-3 px-4">OTA Channel</th>
                    <th class="py-3 px-4">Export Feed (To OTA)</th>
                    <th class="py-3 px-4">Import iCal URL</th>
                    <th class="py-3 px-4 text-center">Markup</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mappings as $mapping)
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="py-3 px-4">
                        <div class="font-medium text-slate-800">{{ $mapping->unit?->title }}</div>
                        <div class="text-xs text-slate-400">{{ $mapping->unit?->site?->name ?? 'General' }} ({{ $mapping->unit?->unit_code }})</div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs" style="background-color: {{ $mapping->channel_meta['color'] }};">
                                <i class="{{ $mapping->channel_meta['icon'] }}"></i>
                            </span>
                            <span class="font-medium text-slate-700">{{ $mapping->channel_meta['name'] }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-1.5" x-data="{ copied: false }">
                            <input type="text" readonly value="{{ $mapping->export_url }}" class="text-xs bg-slate-50 border border-slate-200 rounded px-2 py-1 font-mono w-48 text-slate-600 truncate select-all">
                            <button type="button" @click="navigator.clipboard.writeText('{{ $mapping->export_url }}'); copied = true; setTimeout(() => copied = false, 2000)" class="p-1 text-slate-400 hover:text-primary transition" title="Copy export URL to paste into OTA">
                                <i class="fas" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400">Paste into {{ $mapping->channel_meta['name'] }} Calendar Export</span>
                    </td>
                    <td class="py-3 px-4 max-w-xs truncate" title="{{ $mapping->import_ical_url }}">
                        @if($mapping->import_ical_url)
                        <span class="text-xs font-mono text-slate-700 truncate block max-w-[200px]">{{ $mapping->import_ical_url }}</span>
                        @if($mapping->last_synced_at)
                        <span class="text-[10px] text-slate-400">Synced: {{ $mapping->last_synced_at->diffForHumans() }}</span>
                        @endif
                        @else
                        <span class="text-xs text-amber-600 italic">No import URL configured</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="font-mono text-xs font-semibold text-slate-700">{{ number_format($mapping->rate_multiplier, 2) }}x</span>
                        <div class="text-[10px] text-slate-400">+{{ number_format(($mapping->rate_multiplier - 1) * 100) }}%</div>
                    </td>
                    <td class="py-3 px-4 text-center">
                        @switch($mapping->sync_status)
                            @case('success')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    <i class="fas fa-check-circle mr-1"></i> Active
                                </span>
                                @break
                            @case('conflict')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800" title="Double booking collision blocked">
                                    <i class="fas fa-shield-alt mr-1"></i> Conflict Blocked
                                </span>
                                @break
                            @case('error')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800" title="{{ $mapping->last_error }}">
                                    <i class="fas fa-times-circle mr-1"></i> Error
                                </span>
                                @break
                            @case('syncing')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Syncing
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                    Idle
                                </span>
                        @endswitch
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($mapping->import_ical_url)
                            <form method="POST" action="{{ route('admin.channel-manager.mapping.sync', $mapping) }}">
                                @csrf
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-primary hover:bg-slate-100 rounded transition" title="Sync now">
                                    <i class="fas fa-sync text-xs"></i>
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('admin.channel-manager.mapping.toggle', $mapping) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-slate-100 rounded transition" title="{{ $mapping->is_active ? 'Pause connection' : 'Resume connection' }}">
                                    <i class="fas {{ $mapping->is_active ? 'fa-pause' : 'fa-play' }} text-xs"></i>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.channel-manager.mapping.destroy', $mapping) }}" onsubmit="return confirm('Remove this OTA channel mapping?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Delete connection">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        <i class="fas fa-network-wired text-3xl mb-2 text-slate-300"></i>
                        <p>No OTA channel connections configured yet. Click "Add Connection" to connect Airbnb, Booking.com or VRBO.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add Channel Mapping Modal -->
    <div x-show="addModalOpen" x-transition class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-lg w-full p-6" @click.outside="addModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Connect OTA Channel Feed</h3>
                <button @click="addModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.channel-manager.mapping.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Property / Unit</label>
                    <select name="property_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">-- Choose Unit --</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->title }} ({{ $unit->unit_code }}) - ₦{{ number_format($unit->price_per_night) }}/night</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">OTA Platform</label>
                        <select name="channel" x-model="selectedChannel" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="airbnb">Airbnb</option>
                            <option value="bookingcom">Booking.com</option>
                            <option value="vrbo">VRBO / HomeAway</option>
                            <option value="expedia">Expedia</option>
                            <option value="other">Other OTA Feed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rate Markup Multiplier</label>
                        <input type="number" step="0.01" min="0.5" max="3.0" name="rate_multiplier" value="1.15" placeholder="1.15 (= +15%)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                        <span class="text-[10px] text-slate-400">Compensates for OTA channel fees</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Import iCal URL (From Airbnb/Booking.com/VRBO)</label>
                    <input type="url" name="import_ical_url" placeholder="https://www.airbnb.com/calendar/ical/..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                    <span class="text-[10px] text-slate-400">Copy the calendar export link from your listing on Airbnb / Booking.com</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Stay Override (Nights)</label>
                    <input type="number" min="1" max="365" name="min_stay_override" placeholder="Optional (e.g. 2)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addModalOpen = false" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs font-medium shadow-sm transition">
                        Save Connection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
