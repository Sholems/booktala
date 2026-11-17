<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Site;
use App\Models\User;
use App\Services\SecurityService;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Pages shared by the staff and admin portals that work on one site at a time:
 * staff pick among the sites they are assigned to, head office among all.
 */
trait SiteScopedPortal
{
    protected function portal(Request $request): string
    {
        return $request->routeIs('admin.*') ? 'admin' : 'staff';
    }

    /** @return Collection<int, Site> */
    protected function portalSites(User $user): Collection
    {
        return Site::where('is_active', true)
            ->when(!PropertyAccess::seesAll($user), fn ($q) => $q->whereIn('id', PropertyAccess::activeSiteIds($user)))
            ->orderBy('name')
            ->get();
    }

    /**
     * The site chosen with ?site= (or the first one), checked against what the user may see.
     */
    protected function selectedSite(Request $request, Collection $sites): ?Site
    {
        if ($request->filled('site')) {
            return $sites->firstWhere('id', $request->integer('site')) ?? abort(404);
        }

        return $sites->first();
    }

    protected function ensureSees(User $user, ?int $siteId): void
    {
        abort_unless($siteId && (PropertyAccess::seesAll($user) || PropertyAccess::activeSiteIds($user)->pluck('site_id')->contains($siteId)), 404);
    }

    protected function ensureManages(User $user, ?int $siteId): void
    {
        abort_unless(SecurityService::canManage($user, $siteId), 403, 'Only the site supervisor or head office can do this.');
    }

    protected function viewData(Request $request, array $data): array
    {
        $portal = $this->portal($request);

        return $data + ['layout' => 'layouts.' . $portal, 'p' => $portal];
    }
}
