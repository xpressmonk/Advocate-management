<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(
            $request->user() && Roles::can((string) ($request->user()->role ?: 'staff'), $permission),
            403,
            'You do not have permission to perform this action.',
        );

        return $next($request);
    }
}
