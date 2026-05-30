<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// 最外層引用用到的 Controller
use App\Http\Controllers\AuthController;
// 最外層引用用到的 Middleware
use App\Http\Middleware\CheckToken;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// 註解上面用不上的程式碼

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ==========================================
// 2. 需要 Token 驗證的 API (使用寫好的 CheckToken)
// ==========================================
Route::middleware([CheckToken::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
