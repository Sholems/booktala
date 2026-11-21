@extends('emails.layouts.marketing')

@section('content')
<div style="text-align: center; margin-bottom: 24px;">
    <span style="display: inline-block; background-color: #ecfdf5; color: #047857; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 20px;">
        THANK YOU FOR STAYING WITH US
    </span>
</div>

<h1 style="color: #0B2D5B; font-size: 24px; font-weight: 800; line-height: 1.3; margin: 0 0 16px; text-align: center; font-family: Georgia, serif;">
    How Was Your Stay at {{ $reservation->property->name ?? 'BookTala' }}?
</h1>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    Hello {{ $reservation->guest->first_name ?? ($reservation->user->first_name ?? 'Valued Guest') }},
</p>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    We hope you enjoyed your time with us at BookTala. Our team strives to deliver a seamless, five-star hospitality experience on every visit.
</p>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
    Could you take 60 seconds to share your feedback? Your review directly helps us continually elevate our services.
</p>

<!-- Feedback Rating Stars -->
<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; margin: 28px 0;">
    <div style="font-size: 28px; margin-bottom: 12px; letter-spacing: 4px;">
        ⭐⭐⭐⭐⭐
    </div>
    <div style="font-size: 13px; font-weight: 700; color: #0B2D5B; margin-bottom: 16px;">
        Rate Your Experience
    </div>
    <a href="{{ route('reviews.create', ['reservation' => $reservation->id]) }}" target="_blank" class="btn-primary" style="background-color: #0B2D5B; color: #ffffff !important; padding: 12px 28px; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block;">
        Leave a Review & Claim ₦10,000 Reward Voucher →
    </a>
</div>

<p style="font-size: 13px; color: #64748b; text-align: center; margin: 0;">
    As a thank-you for your review, a ₦10,000 repeat stay credit will be automatically attached to your profile.
</p>
@endsection
