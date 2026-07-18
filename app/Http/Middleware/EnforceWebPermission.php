<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces Spatie permissions for named web CRM routes.
 * Mapping lives in config/web_permissions.php.
 */
class EnforceWebPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Unauthenticated routes are handled by auth middleware where required.
        if (!$user) {
            return $next($request);
        }

        // Client portal is role-gated separately.
        if ($user->hasRole('client') && ($request->routeIs('client.*') || $request->is('client/*'))) {
            return $next($request);
        }

        // Impersonation leave, profile, auth flows must stay available.
        if ($request->routeIs(
            'impersonate.stop',
            'logout',
            'profile.*',
            'password.*',
            'verification.*',
            'dashboard',
            'notifications.*'
        )) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $permission = $this->resolvePermission($routeName, $request);

        if ($permission === null) {
            return $next($request);
        }

        $permissions = is_array($permission) ? $permission : [$permission];

        foreach ($permissions as $perm) {
            if ($user->can($perm)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this resource.');
    }

    /**
     * @return string|array<int, string>|null
     */
    protected function resolvePermission(?string $routeName, Request $request): string|array|null
    {
        $map = config('web_permissions.map', []);

        if ($routeName) {
            if (isset($map[$routeName])) {
                return $map[$routeName];
            }

            // Wildcard: employees.* → employees.
            foreach ($map as $pattern => $permission) {
                if (!str_contains($pattern, '*')) {
                    continue;
                }
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $routeName)) {
                    return $permission;
                }
            }
        }

        // Fallback URI patterns for unnamed / legacy routes
        $uriMap = config('web_permissions.uri', []);
        $path = '/' . ltrim($request->path(), '/');

        foreach ($uriMap as $pattern => $permission) {
            if ($this->pathMatches($path, $pattern)) {
                return $permission;
            }
        }

        return null;
    }

    protected function pathMatches(string $path, string $pattern): bool
    {
        $regex = '#^' . str_replace(['*', '/'], ['.*', '\/'], $pattern) . '$#i';

        return (bool) preg_match($regex, $path);
    }
}
