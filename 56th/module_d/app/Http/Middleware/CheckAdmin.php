<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->current_user;
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin role required'], 403);
        }

        return $next($request);
    }
}
