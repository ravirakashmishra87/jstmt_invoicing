<?php

namespace App\Http\Middleware;

use Closure;

class EnsurePermission
{
    public function handle($request, Closure $next, string $permission)
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
