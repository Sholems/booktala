<?php

namespace App\Http\Middleware;

use App\Support\Supervision;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Team pages are for staff who supervise or manage at least one site.
 */
class EnsureSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Supervision::isSupervisor($request->user()), 403, 'Only site supervisors and managers can open team pages.');

        return $next($request);
    }
}
