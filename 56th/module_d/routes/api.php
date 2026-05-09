<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AlbumController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('albums')->group(function () {
    // 當訪問 /api/albums 時執行 index
    Route::get('/', [AlbumController::class, 'index']);
});
