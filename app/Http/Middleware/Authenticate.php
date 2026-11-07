<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        }

        if (! auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'আপনার অ্যাকাউন্টটি নিষ্ক্রিয় করা হয়েছে।');
        }

        return $next($request);
    }
}
