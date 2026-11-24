@props(['action'])

@if(config('turnstile.enabled'))
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @if(config('turnstile.site_key'))
            <div class="flex justify-center">
                <div class="cf-turnstile"
                     data-sitekey="{{ config('turnstile.site_key') }}"
                     data-action="{{ $action }}"
                     data-theme="auto"
                     data-retry="auto"></div>
            </div>
        @else
            <p class="text-sm text-red-600">Security verification is temporarily unavailable.</p>
        @endif

        @error('cf-turnstile-response')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
