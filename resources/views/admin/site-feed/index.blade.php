@extends('layouts.admin')
@section('page_title', 'Site Operations Feeds')

@section('content')
<div class="py-6">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-indigo-100 text-indigo-800">
                        <i class="fas fa-broadcast-tower text-lg"></i>
                    </span>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Site Operations Feeds</h1>
                        <p class="text-sm text-gray-500">Cross-site operational communication, supervisor dispatches, and emergency alerts</p>
                    </div>
                </div>
            </div>

            @if($sites->isNotEmpty() && $currentSite)
            <div class="flex items-center gap-2">
                <label for="admin-site-select" class="text-xs font-semibold text-gray-600 uppercase">Selected Site:</label>
                <select id="admin-site-select" onchange="window.location.href = this.value" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white shadow-sm focus:ring-primary focus:border-primary">
                    @foreach($sites as $site)
                    <option value="{{ route('admin.site-feed.index', ['site_id' => $site->id, 'category' => $selectedCategory]) }}" @selected($site->id === $currentSite->id)>
                        {{ $site->name }} ({{ $site->properties_count ?? $site->properties()->count() }} units)
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <i class="fas fa-check-circle text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(!$currentSite)
        <div class="bg-white rounded-lg p-10 text-center ring-1 ring-gray-200">
            <i class="fas fa-map-marker-slash text-gray-300 text-4xl mb-3"></i>
            <h3 class="text-base font-semibold text-gray-700">No active sites found</h3>
            <p class="text-sm text-gray-500 mt-1">Configure sites in the Sites section before using site feeds.</p>
            <div class="mt-4">
                <a href="{{ route('admin.sites.index') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-plus mr-1.5"></i> Manage Sites
                </a>
            </div>
        </div>
        @else

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left / Main Column: The Live Feed -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Category Filter Pills -->
                <div class="bg-white p-2.5 rounded-lg shadow-sm ring-1 ring-gray-200 flex flex-wrap gap-1.5 text-xs">
                    <a href="{{ route('admin.site-feed.index', ['site_id' => $currentSite->id, 'category' => 'all']) }}"
                       class="px-3 py-1.5 rounded-md font-medium transition {{ $selectedCategory === 'all' ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        All Updates
                    </a>
                    @foreach($categories as $catKey => $catInfo)
                    <a href="{{ route('admin.site-feed.index', ['site_id' => $currentSite->id, 'category' => $catKey]) }}"
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

                        <!-- Action Controls (Pin / Delete) -->
                        <div class="flex items-center gap-1">
                            <form method="POST" action="{{ route('admin.site-feed.pin', $msg) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs text-gray-400 hover:text-amber-600 p-1.5 rounded hover:bg-gray-100 transition" title="{{ $msg->pinned ? 'Unpin message' : 'Pin to top' }}">
                                    <i class="fas fa-thumbtack {{ $msg->pinned ? 'text-amber-600' : '' }}"></i>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.site-feed.destroy', $msg) }}" onsubmit="return confirm('Delete this message from site feed?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-600 p-1.5 rounded hover:bg-gray-100 transition" title="Delete message">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
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
                            <span>{{ $msg->created_at->format('M d, Y · g:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
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
                    <p class="text-sm text-gray-500 mt-1">There are no operational updates in this category for {{ $currentSite->name }}.</p>
                </div>
                @endforelse

                <div class="mt-4">
                    {{ $messages->links() }}
                </div>
            </div>

            <!-- Right Column: Admin Dispatch Form -->
            <div class="space-y-4">
                <div class="bg-white rounded-lg p-5 shadow-sm ring-1 ring-gray-200">
                    <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <i class="fas fa-bullhorn text-primary"></i> Head Office Dispatch
                    </h2>
                    <p class="text-xs text-gray-500 mb-4">Broadcast an administrative or operational directive to the staff on duty at {{ $currentSite->name }}.</p>

                    <form method="POST" action="{{ route('admin.site-feed.store') }}" class="space-y-4">
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
                            <label for="unit_id" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tagged Unit <span class="font-normal text-gray-400 lowercase">(optional)</span></label>
                            <select id="unit_id" name="unit_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-primary focus:border-primary">
                                <option value="">-- General / Entire Property --</option>
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
                            <label for="message" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Broadcast Content</label>
                            <textarea id="message" name="message" rows="4" maxlength="2000" required placeholder="Type operational notice or dispatch here..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-primary focus:border-primary">{{ old('message') }}</textarea>
                            @error('message')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="pinned" name="pinned" value="1" @checked(old('pinned')) class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <label for="pinned" class="text-xs font-medium text-gray-700 cursor-pointer">
                                <i class="fas fa-thumbtack text-amber-600 mr-1"></i> Pin notice to the top of site feed
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-slate-900 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-slate-800 transition text-sm flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-paper-plane text-xs"></i> Broadcast Dispatch
                        </button>
                    </form>
                </div>
            </div>

        </div>
        @endif

    </div>
</div>
@endsection
