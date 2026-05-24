<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User; // 引入 User 模型

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

        if (!$authHeader) {
            return response()->json(['success' => false, 'message' => 'Access Token is required'], 401);
        }

        // 2. 解析 Token。題目格式通常是 "Bearer <token>"，我們用最快的方式把 "Bearer " 抹掉
        $token = str_replace('Bearer ', '', $authHeader);

        // 3. 去資料庫撈看有沒有人拿這個 access_token
        $user = User::where('access_token', $token)->first();

        // 4. 找不到使用者 ➡️ 401
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or invalid token'], 401);
        }

        // 5. 檢查這傢伙有沒有被停權 ➡️ 403 
        if ($user->is_banned) {
            return response()->json(['success' => false, 'message' => 'User is banned'], 403);
        }

        // 💡 關鍵大招：把找到的 $user 塞進 Request 裡面
        // 這樣後面的 Controller 就能直接用 $request->get('current_user') 拿到這個人，不用重新撈資料庫！
        $request->merge(['current_user' => $user]);

        return $next($request);
    }
}
