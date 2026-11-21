<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ref = $request->query('ref');
        if (! $ref) {
            return $response;
        }

        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', (string) $ref)));
        if (strlen($code) < 3) {
            return $response;
        }

        $affiliate = Affiliate::where('code', $code)->where('status', 'active')->first();
        if (! $affiliate) {
            return $response;
        }

        // Attach 30-day tracking cookie to response
        $response->withCookie(cookie('bt_affiliate', $affiliate->code, 60 * 24 * 30, null, null, null, false));

        // Record click if not logged in the last 2 hours from this IP
        $recentClick = AffiliateClick::where('affiliate_id', $affiliate->id)
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subHours(2))
            ->exists();

        if (! $recentClick) {
            AffiliateClick::create([
                'affiliate_id' => $affiliate->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'landing_page' => substr($request->fullUrl(), 0, 255),
            ]);
        }

        return $response;
    }
}
