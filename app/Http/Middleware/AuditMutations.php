<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $role = (string) ($request->user()->role ?: 'staff');
            $method = strtoupper($request->method());
            $permission = $method === 'DELETE' ? 'delete' : ($method === 'POST' ? 'create' : 'update');
            if (! Roles::can($role, $permission)) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        $response = $next($request);

        if (! $request->user() || ! in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $action = match (strtoupper($request->method())) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
        };

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'route_name' => (string) ($request->route()?->getName() ?? ''),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'changes' => $this->safeChanges($request),
        ]);

        return $response;
    }

    private function safeChanges(Request $request): array
    {
        $changes = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'mail_password',
            'sms_bearer_token',
        ]);

        return collect($changes)->map(static fn ($value) => is_scalar($value) ? $value : '[submitted data]')
            ->all();
    }
}
