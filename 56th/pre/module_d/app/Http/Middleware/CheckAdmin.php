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
        // CheckAdmin 這個 middleware 會放在 CheckToken 底下，所以可以直接取得當前的使用者
        $user = $request->input('current_user');

        // 需要管理員存取權限 : 非「admin」角色的使用者嘗試存取管理員 API
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }

        return $next($request);
    }
}
