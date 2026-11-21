@extends('emails.layouts.marketing')

@section('content')
<!-- Optional Hero Image -->
@if(!empty($campaign->hero_image_url))
<div style="margin: -36px -32px 28px; text-align: center;">
    <img src="{{ $campaign->hero_image_url }}" alt="BookTala" style="width: 100%; max-width: 600px; height: auto; display: block;" />
</div>
@endif

<!-- Headline -->
@if(!empty($campaign->headline))
<h1 style="color: #0B2D5B; font-size: 24px; font-weight: 800; line-height: 1.3; margin: 0 0 16px; font-family: Georgia, serif;">
    {{ $campaign->headline }}
</h1>
@endif

<!-- Greeting -->
<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    Hello {{ $recipient['name'] ?? 'there' }},
</p>

<!-- Body Content -->
<div style="font-size: 15px; color: #334155; line-height: 1.7; margin: 0 0 24px;">
    {!! nl2br(e($campaign->content_html)) !!}
</div>

<!-- Promo Voucher Callout (if active) -->
@if(!empty($campaign->discount_code))
<div style="background-color: #fef3c7; border: 2px dashed #f59e0b; border-radius: 10px; padding: 20px; text-align: center; margin: 28px 0;">
    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 1.5px; color: #b45309; margin-bottom: 6px;">
        EXCLUSIVE VIP PROMOTION
    </div>
    <div style="font-size: 22px; font-weight: 800; color: #0B2D5B; letter-spacing: 2px; font-family: monospace; background: #ffffff; padding: 6px 16px; border-radius: 6px; display: inline-block; margin-bottom: 6px; border: 1px solid #fde68a;">
        {{ $campaign->discount_code }}
    </div>
    <div style="font-size: 12px; color: #78350f;">
        Apply this promotional code during checkout on BookTala to enjoy your special rate.
    </div>
</div>
@endif

<!-- Featured Apartments Section -->
@if(!empty($properties) && count($properties) > 0)
<div style="margin: 32px 0 24px;">
    <h3 style="font-size: 16px; font-weight: 700; color: #0B2D5B; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 16px; text-align: center;">
        Handpicked Residences For You
    </h3>

    @foreach($properties as $property)
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background-color: #ffffff;">
        <tr>
            <td width="35%" style="vertical-align: top; background-color: #e2e8f0;">
                @php
                    $imageUrl = $property->primary_image ? asset('storage/' . $property->primary_image) : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=400&q=80';
                @endphp
                <img src="{{ $imageUrl }}" alt="{{ $property->name }}" style="width: 100%; height: 110px; object-fit: cover; display: block;" />
            </td>
            <td width="65%" style="padding: 16px; vertical-align: middle;">
                <div style="font-size: 11px; text-transform: uppercase; color: #D97706; font-weight: 700; letter-spacing: 0.5px;">
                    {{ $property->site?->name ?? 'Lagos Premier' }}
                </div>
                <div style="font-size: 15px; font-weight: 700; color: #0B2D5B; margin: 4px 0;">
                    {{ $property->name }}
                </div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">
                    <strong style="color: #0f172a;">₦{{ number_format($property->base_price, 0) }}</strong> / night
                </div>
                <a href="{{ route('properties.show', $property->slug ?? $property->id) }}" style="display: inline-block; background-color: #0B2D5B; color: #ffffff !important; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                    View & Reserve →
                </a>
            </td>
        </tr>
    </table>
    @endforeach
</div>
@endif

<!-- Main Call To Action Button -->
@if(!empty($campaign->cta_text))
<div style="text-align: center; margin: 32px 0 16px;">
    <a href="{{ $campaign->cta_url ?: config('app.url') }}" target="_blank" class="btn-primary" style="background-color: #DC2626; color: #ffffff !important; padding: 14px 32px; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block;">
        {{ $campaign->cta_text }}
    </a>
</div>
@endif
@endsection
