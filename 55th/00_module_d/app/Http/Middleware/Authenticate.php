<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // 預設導回登入頁面，但在這裡我們改為直接返回 null，讓前端處理未授權的情況
        // return $request->expectsJson() ? null : route('login');
        return abort(401); // 修改為直接返回 null，讓前端處理未授權的情況
    }
}
