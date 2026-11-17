{{-- Next step prompt and stay timeline for one reservation ($reservation). --}}
@php
    $next = \App\Support\GuestJourney::next($reservation);
    $steps = \App\Support\GuestJourney::steps($reservation);
    $tones = [
        'gold' => 'border-[#E11D2E] bg-[#E11D2E]/10',
        'green' => 'border-green-300 bg-green-50',
        'blue' => 'border-blue-200 bg-blue-50',
        'amber' => 'border-amber-300 bg-amber-50',
    ];
@endphp

@if($next)
<div class="rounded-xl border p-5 {{ $tones[$next['tone']] ?? $tones['blue'] }}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="font-semibold text-gray-900">{{ $next['title'] }}</p>
            <p class="text-sm text-gray-700">{{ $next['text'] }}</p>
        </div>
        @if($next['url'])
        <a href="{{ $next['url'] }}" class="shrink-0 inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
            {{ $next['action'] }} <i class="fas fa-arrow-right text-xs"></i>
        </a>
        @endif
    </div>
</div>
@endif

<ol class="mt-5 grid gap-3 {{ count($steps) > 2 ? 'sm:grid-cols-5' : 'sm:grid-cols-2' }}" aria-label="Booking progress">
    @foreach($steps as $step)
    <li class="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm
            {{ $step['state'] === 'done' ? 'bg-green-600 text-white' : ($step['state'] === 'current' ? 'bg-primary text-white ring-4 ring-primary/15' : 'bg-gray-100 text-gray-400') }}">
            @if($step['state'] === 'done')<i class="fas fa-check text-xs"></i>@else{{ $loop->iteration }}@endif
        </span>
        <span>
            <span class="block text-sm font-medium {{ $step['state'] === 'upcoming' ? 'text-gray-500' : 'text-gray-900' }}">{{ $step['label'] }}</span>
            @if($step['detail'])<span class="block text-xs text-gray-500">{{ $step['detail'] }}</span>@endif
        </span>
    </li>
    @endforeach
</ol>
