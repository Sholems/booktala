@php
$siteName = \App\Models\Setting::get('site_name', config('brand.name', 'BookTala'));
$phone = config('brand.phone', '+234 814 000 8252');
$email = config('brand.email', 'concierge@booktala.com');
$logoPath = public_path('images/booktala-logo.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>BookTala Partner Material — {{ $affiliate->code }}</title>
    <style>
        @page {
            margin: 12mm 15mm;
            size: a4 {{ $layout === 'tent' ? 'landscape' : 'portrait' }};
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .header-box {
            background-color: #0B2D5B;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            margin: 8px 0 4px 0;
            color: #ffffff;
        }
        .header-sub {
            font-size: 11px;
            color: #cbd5e1;
            margin: 0;
        }
        .tag-pill {
            display: inline-block;
            background-color: #f59e0b;
            color: #0B2D5B;
            font-size: 9px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .qr-card {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
        }
        .qr-img {
            width: 180px;
            height: 180px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 6px;
            border-radius: 8px;
        }
        .feature-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .feature-cell {
            width: 50%;
            padding: 8px;
            vertical-align: top;
        }
        .feature-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 8px;
        }
        .feature-title {
            font-weight: bold;
            font-size: 11px;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .feature-desc {
            font-size: 9px;
            color: #64748b;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 20px;
            font-size: 10px;
            color: #64748b;
        }
        /* Tent Fold Layout Styles */
        .tent-col {
            width: 48%;
            vertical-align: top;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background-color: #ffffff;
        }
        .fold-divider {
            width: 4%;
            text-align: center;
            vertical-align: middle;
            border-left: 1px dashed #cbd5e1;
        }
    </style>
</head>
<body>

@if($layout === 'tent')
    <!-- TABLE TENT FOLD TEMPLATE -->
    <table style="width: 100%; height: 100%; border-collapse: collapse;">
        <tr>
            <!-- SIDE 1: GUEST SCAN SIDE -->
            <td class="tent-col" style="text-align: center;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="height: 38px; margin-bottom: 8px;">
                @endif
                <div class="tag-pill" style="margin-bottom: 8px;">Luxury Stays · Lagos</div>
                <div style="font-size: 18px; font-weight: bold; color: #0B2D5B; margin-bottom: 4px;">Experience 5-Star Living</div>
                <div style="font-size: 10px; color: #64748b; margin-bottom: 12px;">Lekki Phase 1 · Victoria Island · Ikoyi</div>

                <div style="margin: 10px 0;">
                    <img src="{{ $qrDataUri }}" class="qr-img" style="width: 140px; height: 140px;">
                </div>

                <div style="font-size: 10px; font-weight: bold; color: #0B2D5B; margin-top: 4px;">
                    Scan with any smartphone camera
                </div>
                <div style="font-size: 9px; font-family: monospace; color: #64748b;">
                    Partner Code: <strong>{{ $affiliate->code }}</strong>
                </div>

                <div style="font-size: 9px; color: #94a3b8; margin-top: 12px;">
                    Guaranteed 24/7 Power · High-Speed WiFi · Concierge
                </div>
            </td>

            <td class="fold-divider">
                <div style="font-size: 8px; color: #94a3b8; letter-spacing: 2px;">
                    FOLD&nbsp;HERE
                </div>
            </td>

            <!-- SIDE 2: AMENITIES & REVERSE SIDE -->
            <td class="tent-col" style="text-align: center;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="height: 38px; margin-bottom: 8px;">
                @endif
                <div class="tag-pill" style="background-color: #10b981; color: #ffffff; margin-bottom: 8px;">100% Hospitality Guarantee</div>
                <div style="font-size: 18px; font-weight: bold; color: #0B2D5B; margin-bottom: 12px;">Why Stay with BookTala?</div>

                <div style="text-align: left; margin: 10px 0;">
                    <div class="feature-box" style="margin-bottom: 8px;">
                        <div class="feature-title">✦ Guaranteed 24/7 Electricity</div>
                        <div class="feature-desc">Dual industrial generators with zero downtime.</div>
                    </div>
                    <div class="feature-box" style="margin-bottom: 8px;">
                        <div class="feature-title">✦ High-Speed Fiber WiFi</div>
                        <div class="feature-desc">Dedicated fiber internet ready for Zoom and streaming.</div>
                    </div>
                    <div class="feature-box" style="margin-bottom: 8px;">
                        <div class="feature-title">✦ Gated 24/7 Security &amp; Concierge</div>
                        <div class="feature-desc">Uniformed security personnel, CCTV, and concierge care.</div>
                    </div>
                    <div class="feature-box">
                        <div class="feature-title">✦ Daily Hotel Housekeeping</div>
                        <div class="feature-desc">Fresh crisp linens, toiletries, and sanitization.</div>
                    </div>
                </div>

                <div style="font-size: 9px; color: #64748b; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 8px;">
                    Concierge &amp; WhatsApp: <strong>{{ $phone }}</strong> · {{ $siteName }}
                </div>
            </td>
        </tr>
    </table>

@else
    <!-- A4 FULL FLYER TEMPLATE -->
    <div class="header-box">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="height: 44px; margin-bottom: 8px; background: #ffffff; padding: 6px 12px; border-radius: 8px;">
        @endif
        <br>
        <span class="tag-pill">Luxury Serviced Apartments · Lagos</span>
        <div class="header-title">Comfort of Home, Service of a 5-Star Hotel</div>
        <div class="header-sub">Fully furnished apartments in Lekki Phase 1, Victoria Island &amp; Ikoyi</div>
    </div>

    <table class="feature-table">
        <tr>
            <td class="feature-cell">
                <div class="feature-box">
                    <div class="feature-title">✦ Guaranteed 24/7 Power</div>
                    <div class="feature-desc">Dual heavy-duty generators ensure zero power downtime.</div>
                </div>
            </td>
            <td class="feature-cell">
                <div class="feature-box">
                    <div class="feature-title">✦ Superfast Fiber Internet</div>
                    <div class="feature-desc">Unlimited high-speed WiFi for remote work and streaming.</div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="feature-cell">
                <div class="feature-box">
                    <div class="feature-title">✦ 24/7 Gated Security</div>
                    <div class="feature-desc">CCTV monitoring, secure access control &amp; armed guard patrols.</div>
                </div>
            </td>
            <td class="feature-cell">
                <div class="feature-box">
                    <div class="feature-title">✦ Hotel-Grade Housekeeping</div>
                    <div class="feature-desc">Daily housekeeping, fresh crisp linens, and concierge care.</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="qr-card">
        <div class="tag-pill" style="margin-bottom: 10px;">Scan to Browse &amp; Book Direct</div>
        <br>
        <img src="{{ $qrDataUri }}" class="qr-img">
        <div style="font-size: 11px; font-weight: bold; color: #0B2D5B; margin-top: 8px;">
            Point your smartphone camera to check live availability
        </div>
        <div style="font-size: 10px; font-family: monospace; color: #64748b; margin-top: 3px;">
            Official Partner Referral Code: <strong>{{ $affiliate->code }}</strong>
        </div>
    </div>

    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                <strong style="color: #0f172a;">{{ $siteName }} Hospitality &amp; Stays</strong><br>
                Direct Concierge: {{ $phone }} · {{ $email }}
            </td>
            <td style="text-align: right; font-family: monospace;">
                {{ url('/') }}
            </td>
        </tr>
    </table>
@endif

</body>
</html>
