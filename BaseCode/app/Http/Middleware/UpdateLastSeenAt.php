<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class UpdateLastSeenAt
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            //Dùng updateQuietly giúp nghi nhận thời gian nhanh mà không cần event/observer
            Auth::user()->updateQuietly(['last_seen_at' => now(),]);
        }
        return $next($request);
    }
}
