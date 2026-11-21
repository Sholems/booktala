@extends('emails.layouts.marketing')

@section('content')
<div style="text-align: center; margin-bottom: 24px;">
    <span style="display: inline-block; background-color: #fef3c7; color: #b45309; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; padding: 6px 14px; border-radius: 20px;">
        VIP MEMBERSHIP CONFIRMED
    </span>
</div>

<h1 style="color: #0B2D5B; font-size: 24px; font-weight: 800; line-height: 1.3; margin: 0 0 16px; text-align: center; font-family: Georgia, serif;">
    Welcome to BookTala Luxury Stays
</h1>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    Hello {{ $subscriber->name ?: 'Valued Guest' }},
</p>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
    Thank you for joining the <strong>BookTala VIP Club</strong>. You now have priority access to our premier portfolio of fully furnished, hotel-serviced apartments across Lagos and Abuja.
</p>

<!-- Voucher Box -->
<div style="background-color: #f8fafc; border: 2px dashed #0B2D5B; border-radius: 12px; padding: 24px; text-align: center; margin: 28px 0;">
    <div style="font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; color: #64748b; margin-bottom: 8px;">
        Your Welcome Privilege Gift
    </div>
    <div style="font-size: 26px; font-weight: 800; color: #DC2626; letter-spacing: 3px; font-family: monospace; background: #ffffff; padding: 8px 20px; border-radius: 8px; display: inline-block; margin-bottom: 8px; border: 1px solid #e2e8f0;">
        WELCOME10
    </div>
    <div style="font-size: 13px; font-weight: 600; color: #0B2D5B; margin-bottom: 4px;">
        Enjoy 10% Off Your First Booking
    </div>
    <div style="font-size: 12px; color: #64748b;">
        Valid for any stay reserved directly on BookTala.com.
    </div>
</div>

<p style="font-size: 15px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
    Every BookTala reservation includes guaranteed 24/7 power, high-speed fiber internet, premium linen, and dedicated concierge support to make your stay effortless.
</p>

<div style="text-align: center; margin: 32px 0 16px;">
    <a href="{{ config('app.url') }}" target="_blank" class="btn-primary" style="background-color: #DC2626; color: #ffffff !important; padding: 14px 32px; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block;">
        Browse VIP Apartments & Redeem 10% →
    </a>
</div>
@endsection
