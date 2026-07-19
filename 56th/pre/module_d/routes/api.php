<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// 最外層引用用到的 Controller
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\SongController;
// 最外層引用用到的 Middleware
use App\Http\Middleware\CheckToken;
use App\Http\Middleware\CheckAdmin;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// 註解上面用不上的程式碼

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/albums', [AlbumController::class, 'index']);
Route::get('/albums/{album_id}', [AlbumController::class, 'show']);
Route::get('/albums/{album_id}/cover', [AlbumController::class, 'showCover']);
Route::get('/albums/{album_id}/songs', [SongController::class, 'index']);

Route::get('/songs', [SongController::class, 'all']);
Route::get('/songs/{song_id}/cover', [SongController::class, 'showCover']);

// ==========================================
// 2. 需要 Token 驗證的 API (使用寫好的 CheckToken)
// ==========================================
Route::middleware([CheckToken::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/songs/{song_id}', [SongController::class, 'show']);

    // ==========================================
    // 3. 管理員專屬 API (使用寫好的 CheckAdmin)
    // ==========================================
    Route::middleware([CheckAdmin::class])->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user_id}', [AdminController::class, 'update']);
        Route::put('/users/{user_id}/ban', [AdminController::class, 'ban']);
        Route::put('/users/{user_id}/unban', [AdminController::class, 'unban']);

        Route::post('/albums', [AlbumController::class, 'store']);
        Route::put('/albums/{album_id}', [AlbumController::class, 'update']);
        Route::delete('/albums/{album_id}', [AlbumController::class, 'destroy']);

        Route::post('/albums/{album_id}/songs', [SongController::class, 'store']);
        Route::put('/albums/{album_id}/songs/order', [SongController::class, 'updateOrder']);
        Route::post('/albums/{album_id}/songs/{song_id}', [SongController::class, 'update']);
        Route::delete('/albums/{album_id}/songs/{song_id}', [SongController::class, 'destroy']);
    });
});

Route::any('{any}', function () {
    return response()->json(['success' => false, 'message' => 'Not Found'], 404);
})->where('any', '.*');
