@extends('layouts.staff')
@section('page_title', 'Site Operations Radio & Feed')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-blue-100 text-blue-800">
                        <i class="fas fa-broadcast-tower text-lg"></i>
                    </span>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Site Operations Feed</h1>
                        <p class="text-sm text-gray-500">Live operational broadcasts, urgent notes, and unit updates for <strong>{{ $currentSite->name }}</strong></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($sites->count() > 1)
                <div class="flex items-center gap-2">
                    <label for="site-select" class="text-xs font-semibold text-gray-600 uppercase">Site:</label>
                    <select id="site-select" onchange="window.location.href = this.value" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white shadow-sm focus:ring-primary focus:border-primary">
                        @foreach($sites as $site)
                        <option value="{{ route('staff.team.feed.index', ['site_id' => $site->id, 'category' => $selectedCategory]) }}" @selected($site->id === $currentSite->id)>
                            {{ $site->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <a href="{{ route('staff.dashboard') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-1.5 text-gray-400"></i> Dashboard
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left / Main Column: The Live Feed -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Category Filter Pills -->
                <div class="bg-white p-2.5 rounded-lg shadow-sm ring-1 ring-gray-200 flex flex-wrap gap-1.5 text-xs">
                    <a href="{{ route('staff.team.feed.index', ['site_id' => $currentSite->id, 'category' => 'all']) }}"
                       class="px-3 py-1.5 rounded-md font-medium transition {{ $selectedCategory === 'all' ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        All Updates
                    </a>
                    @foreach($categories as $catKey => $catInfo)
                    <a href="{{ route('staff.team.feed.index', ['site_id' => $currentSite->id, 'category' => $catKey]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition {{ $selectedCategory === $catKey ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="fas {{ $catInfo['icon'] }} text-xs"></i>
                        <span>{{ $catInfo['label'] }}</span>
                    </a>
                    @endforeach
                </div>

                <!-- Messages List -->
                @forelse($messages as $msg)
                @php
                    $catMeta = $categories[$msg->category] ?? ['label' => ucfirst($msg->category), 'color' => 'bg-gray-100 text-gray-800 border-gray-300', 'icon' => 'fa-info-circle'];
                @endphp
                <div class="bg-white rounded-lg p-5 shadow-sm ring-1 ring-gray-200 transition {{ $msg->pinned ? 'border-l-4 border-amber-500 bg-amber-50/20' : '' }}">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $catMeta['color'] }}">
                                <i class="fas {{ $catMeta['icon'] }} text-[10px]"></i>
                                {{ $catMeta['label'] }}
                            </span>

                            @if($msg->unit)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                <i class="fas fa-door-closed text-[10px] text-slate-500"></i>
                                {{ $msg->unit->title }}
                            </span>
                            @endif

                            @if($msg->pinned)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900 border border-amber-200">
                                <i class="fas fa-thumbtack text-[10px] text-amber-600"></i> Pinned Notice
                            </span>
                            @endif
                        </div>

                        <!-- Supervisor Controls -->
                        @if($isSupervisor)
                        <form method="POST" action="{{ route('staff.team.feed.pin', $msg) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs text-gray-400 hover:text-amber-600 px-2 py-1 rounded hover:bg-gray-100 transition" title="{{ $msg->pinned ? 'Unpin message' : 'Pin to top' }}">
                                <i class="fas fa-thumbtack {{ $msg->pinned ? 'text-amber-600' : '' }}"></i>
                                <span class="sr-only">{{ $msg->pinned ? 'Unpin' : 'Pin' }}</span>
                            </button>
                        </form>
                        @endif
                    </div>

                    <!-- Message Body -->
                    <div class="text-gray-800 text-sm whitespace-pre-line leading-relaxed mb-3">
                        {{ $msg->message }}
                    </div>

                    <!-- Footer: Author & Timestamp -->
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-700">{{ $msg->user->name ?? 'Staff' }}</span>
                            <span>•</span>
                            <span>{{ $msg->created_at->diffForHumans() }}</span>
                        </div>
                        @if($msg->pinned && $msg->pinnedByUser)
                        <span class="text-[11px] text-amber-800 italic">
                            Pinned by {{ $msg->pinnedByUser->name }}
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-lg p-10 text-center ring-1 ring-gray-200">
                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                    <h3 class="text-base font-semibold text-gray-700">No updates found</h3>
                    <p class="text-sm text-gray-500 mt-1">There are no notices in this category for {{ $currentSite->name }}.</p>
                </div>
                @endforelse

                <div class="mt-4">
                    {{ $messages->links() }}
                </div>
            </div>

            <!-- Right Column: Broadcast Form -->
            <div class="space-y-4">
                <div class="bg-white rounded-lg p-5 shadow-sm ring-1 ring-gray-200">
                    <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <i class="fas fa-paper-plane text-primary"></i> Post Operational Notice
                    </h2>
                    <p class="text-xs text-gray-500 mb-4">Broadcast an update to all colleagues and supervisors working at {{ $currentSite->name }}.</p>

                    <form method="POST" action="{{ route('staff.team.feed.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="site_id" value="{{ $currentSite->id }}">

                        <div>
                            <label for="category" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Category</label>
                            <select id="category" name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-primary focus:border-primary">
                                @foreach($categories as $catKey => $catInfo)
                                <option value="{{ $catKey }}" @selected(old('category') === $catKey)>
                                    {{ $catInfo['label'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('category')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="unit_id" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tagged Unit / Flat <span class="font-normal text-gray-400 lowercase">(optional)</span></label>
                            <select id="unit_id" name="unit_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-primary focus:border-primary">
                                <option value="">-- General / Whole Site --</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                    {{ $unit->title }}
                                </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Operational Message</label>
                            <textarea id="message" name="message" rows="4" maxlength="2000" required placeholder="e.g. Generator scheduled for refueling at 2 PM. Water pump at Block B serviced." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-primary focus:border-primary">{{ old('message') }}</textarea>
                            @error('message')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($isSupervisor)
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="pinned" name="pinned" value="1" @checked(old('pinned')) class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <label for="pinned" class="text-xs font-medium text-gray-700 cursor-pointer">
                                <i class="fas fa-thumbtack text-amber-600 mr-1"></i> Pin notice to the top of the feed
                            </label>
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-slate-900 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-slate-800 transition text-sm flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-bullhorn text-xs"></i> Post to Site Radio
                        </button>
                    </form>
                </div>

                <!-- Info Card -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-lg p-4 text-xs text-blue-900 space-y-2">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fas fa-info-circle text-blue-600"></i> Site Communication Guidelines
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Use <strong>Urgent Alert</strong> for power cuts, plumbing leaks, or guest safety matters.</li>
                        <li>Tag the specific <strong>Unit</strong> so the next shift knows exactly where to follow up.</li>
                        <li>Messages are visible to all colleagues assigned to {{ $currentSite->name }}.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
