@if($stats && ($sectionsArray['stats']->is_visible ?? true))
<section class="relative z-20 mb-8">
    <div class="container mx-auto px-4 md:px-6">
        <dl class="mx-auto max-w-5xl grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min(count($stats), 5) }} gap-y-5 rounded-2xl bg-white shadow-md shadow-primary/5 ring-1 ring-gray-200/70 px-4 py-5 md:py-6 lg:divide-x lg:divide-gray-200">
            @foreach($stats as $stat)
            @php
                preg_match('/^([^\d]*)([\d.,]+)(.*)$/', (string) $stat['value'], $parts);
                $statNumber = isset($parts[2]) ? (float) str_replace(',', '', $parts[2]) : null;
            @endphp
            <div class="flex flex-col-reverse items-center text-center px-2 {{ $loop->last && count($stats) % 2 ? 'col-span-2 sm:col-span-1' : '' }}">
                <dt class="mt-0.5 text-sm font-medium text-gray-600">{{ $stat['label'] }}</dt>
                <dd class="text-2xl md:text-3xl font-bold tracking-tight text-primary">
                    @if($statNumber !== null)
                    {{ $parts[1] }}<span class="stat-count" data-target="{{ $statNumber }}">{{ $parts[2] }}</span>{{ $parts[3] }}
                    @else
                    {{ $stat['value'] }}
                    @endif
                </dd>
            </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
