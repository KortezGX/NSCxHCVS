<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController; // 引入 AuthController
use App\Http\Controllers\AdminController; // 引入 AdminController
use App\Http\Middleware\CheckToken; // 引入自訂的 CheckToken middleware
use App\Http\Middleware\CheckAdmin; // 引入自訂的 CheckAdmin middleware

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 這裡可以放「取得所有專輯」、「取得歌曲」等公開查詢 API

// ==========================================
// 2. 需要 Token 驗證的 API (掛上我們寫的 CheckToken)
// ==========================================
Route::middleware([CheckToken::class])->group(function () {

    // 一般使用者登出
    Route::post('/logout', [AuthController::class, 'logout']);

    // 這裡可以放「看歌曲詳細」、「看統計結果」等使用者 API

    // ==========================================
    // 3. 管理員專屬 API
    // 為了最簡化，直接在 route 裡檢查 role，不另外開 Middleware 檔案！
    // ==========================================
    Route::middleware([CheckAdmin::class])->group(function () {
        // 管理員核心功能
        Route::get('/users', [AdminController::class, 'index']);
        Route::put('/users/{id}/role', [AdminController::class, 'updateRole']);
        Route::put('/users/{id}/ban', [AdminController::class, 'toggleBan']);

        // 後續管理員的新增專輯、刪除歌曲也可以直接丟進這個區塊！
    });

});


