<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
// 先在最外層引用
use App\Models\User;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 從 Header 抓取 X-Authorization
        $authHeader = $request->header('X-Authorization');

        // 需要存取權杖
        if (!$authHeader) {
            return response()->json(['success' => false, 'message' => 'Access Token is required'], 401);
        }

        // 2. 解析 Token。題目格式通常是 "Bearer <token>"，我們用最快的方式把 "Bearer " 抹掉
        $token = str_replace('Bearer ', '', $authHeader);

        // 3. 去資料庫撈看有沒有人拿這個 token
        $user = User::where('token', $token)->first();

        // 無效存取權杖
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid Access Token'], 401);
        }

        // 使用者遭封鎖
        if ($user->is_banned) {
            return response()->json(['success' => false, 'message' => 'User is banned'], 403);
        }

        // 關鍵：把找到的 $user 塞進 Request 裡面
        // 這樣後面的 Controller 就能直接用 $request->input('current_user') 拿到這個人，不用重新撈資料庫！
        $request->merge(['current_user' => $user]);

        return $next($request);
    }
}
