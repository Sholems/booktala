<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $subject ?? 'BookTala Luxury Stays' }}</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f1f5f9; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .btn-primary { background-color: #DC2626; color: #ffffff !important; padding: 14px 28px; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 8px; display: inline-block; text-align: center; }
        .btn-gold { background-color: #D97706; color: #ffffff !important; padding: 12px 24px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px; display: inline-block; }
        @media only screen and (max-width: 620px) {
            .main { width: 100% !important; border-radius: 0 !important; }
            .column { width: 100% !important; display: block !important; padding-left: 0 !important; padding-right: 0 !important; }
            .mobile-padding { padding-left: 20px !important; padding-right: 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <!-- Preheader preview text for inbox snippet -->
    @if(!empty($preview_text))
    <div style="display: none; max-height: 0px; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">
        {{ $preview_text }}
    </div>
    @endif

    <center class="wrapper">
        <table class="main" width="100%" cellpadding="0" cellspacing="0">
            <!-- Header Banner -->
            <tr>
                <td style="background-color: #0B2D5B; padding: 28px 32px; text-align: center;">
                    <a href="{{ config('app.url') }}" target="_blank" style="text-decoration: none;">
                        <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                            <tr>
                                <td style="vertical-align: middle; padding-right: 12px;">
                                    <div style="background-color: #ffffff; width: 40px; height: 40px; border-radius: 8px; text-align: center; line-height: 40px; font-weight: 900; color: #0B2D5B; font-size: 20px; font-family: serif;">
                                        BT
                                    </div>
                                </td>
                                <td style="vertical-align: middle; text-align: left;">
                                    <span style="font-size: 22px; font-weight: 800; letter-spacing: 1px; color: #ffffff; display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">BOOK<span style="color: #DC2626;">TALA</span></span>
                                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; display: block;">Luxury Stays · Serviced Living</span>
                                </td>
                            </tr>
                        </table>
                    </a>
                </td>
            </tr>

            <!-- Content Slot -->
            <tr>
                <td class="mobile-padding" style="padding: 36px 32px; background-color: #ffffff;">
                    @yield('content')
                </td>
            </tr>

            <!-- Trust Bar / Perks -->
            <tr>
                <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td class="column" width="33%" style="text-align: center; padding: 8px;">
                                <span style="font-size: 16px;">⚡</span>
                                <div style="font-size: 12px; font-weight: 700; color: #0B2D5B; margin-top: 4px;">24/7 Guaranteed Power</div>
                                <div style="font-size: 11px; color: #64748b;">Zero downtime</div>
                            </td>
                            <td class="column" width="33%" style="text-align: center; padding: 8px;">
                                <span style="font-size: 16px;">🛡️</span>
                                <div style="font-size: 12px; font-weight: 700; color: #0B2D5B; margin-top: 4px;">Verified Clean & Safe</div>
                                <div style="font-size: 11px; color: #64748b;">Hospitality grade</div>
                            </td>
                            <td class="column" width="33%" style="text-align: center; padding: 8px;">
                                <span style="font-size: 16px;">🛎️</span>
                                <div style="font-size: 12px; font-weight: 700; color: #0B2D5B; margin-top: 4px;">Personal Concierge</div>
                                <div style="font-size: 11px; color: #64748b;">Always on call</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td style="background-color: #0f172a; padding: 32px; text-align: center; color: #94a3b8; font-size: 12px; line-height: 1.6;">
                    <p style="margin: 0 0 12px; font-weight: 700; color: #e2e8f0; font-size: 14px;">
                        BookTala Luxury Stays
                    </p>
                    <p style="margin: 0 0 16px; color: #64748b;">
                        Furnished Apartments & Serviced Residences · Lagos & Abuja, Nigeria
                    </p>
                    <p style="margin: 0 0 16px;">
                        <a href="{{ config('app.url') }}" style="color: #94a3b8; text-decoration: underline; margin: 0 8px;">Browse Stays</a> |
                        <a href="{{ config('app.url') }}/affiliate" style="color: #94a3b8; text-decoration: underline; margin: 0 8px;">Affiliate Portal</a> |
                        <a href="{{ config('app.url') }}/contact" style="color: #94a3b8; text-decoration: underline; margin: 0 8px;">Concierge Support</a>
                    </p>
                    <hr style="border: 0; border-top: 1px solid #1e293b; margin: 20px 0;" />
                    <p style="margin: 0 0 8px; font-size: 11px; color: #475569;">
                        You are receiving this communication because you are a registered member, valued guest, or subscribed to BookTala VIP Club.
                    </p>
                    @if(!empty($unsubscribe_url))
                    <p style="margin: 0; font-size: 11px;">
                        To stop receiving marketing promotions, you can 
                        <a href="{{ $unsubscribe_url }}" style="color: #f87171; text-decoration: underline;">unsubscribe with 1-click here</a>.
                    </p>
                    @endif
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
