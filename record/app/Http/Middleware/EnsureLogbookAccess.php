<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogbookAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->fresh()?->canAccessLogbook()) {
            abort(403, 'Access Denied: Employee is not authorized to access Logbook module.');
        }

        return $next($request);
    }
}
