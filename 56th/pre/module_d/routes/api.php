<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// 最外層引用用到的 Controller
use App\Http\Controllers\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// 註解上面用不上的程式碼

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
