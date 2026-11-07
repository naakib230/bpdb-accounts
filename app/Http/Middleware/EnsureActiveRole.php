<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // System Admin has overall access
        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        $activeRole = $user->activeRole();
        if (! $activeRole) {
            abort(403, 'কোনো সক্রিয় ভূমিকা (Active Role) পাওয়া যায়নি।');
        }

        if (! empty($roles) && ! in_array($activeRole->name, $roles, true)) {
            abort(403, 'আপনার বর্তমান ভূমিকা দিয়ে এই পাতায় প্রবেশাধিকার নেই।');
        }

        return $next($request);
    }
}
