<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // 引入 AuthController 的命名空間

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('00_module_d')->group(function () {
    // 公開的 routes : 登入頁面和登入處理
    Route::prefix('login')->name('login.')->group(function () {
        Route::get('/', [AuthController::class, 'show'])->name('show'); // 顯示登入頁面的路由 login.show 實際路徑為 GET /xx_module_d/login
        Route::post('/', [AuthController::class, 'login'])->name('submit'); // 處理登入表單提交的路由 login.submit 實際路徑為 POST /xx_module_d/login
    });

    // 需要登入才能訪問的 routes
    Route::middleware('auth')->group(function () {
        // 登出路由
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // 實際路徑為 POST /xx_module_d/logout
    });
});
