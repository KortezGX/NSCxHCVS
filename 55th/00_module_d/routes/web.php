<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin; // 引入自訂義的 CheckAdmin Middleware 的命名空間
use App\Http\Controllers\AuthController; // 引入 AuthController 的命名空間
use App\Http\Controllers\PublisherController; // 引入 PublisherController 的命名空間
use App\Http\Controllers\BookController; // 引入 BookController 的命名空間

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

Route::prefix('00_module_d')->group(function() {
    // ==========================================
    // 完全公開 route (login/Books/Publishers 詳情)
    // ==========================================
    // 登入路由
    Route::prefix('login')->name('login.')->group(function () {
        Route::get('/', [AuthController::class, 'show'])->name('show'); // 顯示登入頁面的路由 login.show 實際路徑為 GET /00_module_d/login
        Route::post('/', [AuthController::class, 'login'])->name('submit'); // 處理登入表單提交的路由 login.submit 實際路徑為 POST /00_module_d/login
    });

    // 書籍詳情(未隱藏)
    Route::prefix('books')->name('books.')->group(function () {
        Route::get('/01/{book:isbn}', [BookController::class, 'detial'])->name('public'); // 實際路徑為 GET /00_module_d/books/01/{ISBN}
    });

    // 出版社詳情(未停用)
    // Route::prefix('publishers')->name('publishers.')->group(function() {
    //     Route::get('/{publisher}', [PublisherController::class, 'detial'])->name('detial'); // 實際路徑為 GET /00_module_d/publisher/{publisher}
    // });

    // ==========================================
    // 需要登入
    // ==========================================
    Route::middleware('auth')->group(function () {
        // 登出路由
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // 實際路徑為 POST /00_module_d/logout

        // 書籍相關路由(出版社管理員也可使用)
        Route::prefix('books')->name('books.')->group(function () {
            // 顯示書籍列表的路由
            Route::get('/', [BookController::class, 'index'])->name('index'); // 實際路徑為 GET /00_module_d/books
            Route::get('/{book:isbn}', [BookController::class, 'detial'])->name('detial'); // 實際路徑為 GET /00_module_d/books/{ISBN}
            // 新增書籍的路由
            Route::get('/new', [BookController::class, 'create'])->name('create'); // 實際路徑為 GET /00_module_d/books/create
            Route::post('/', [BookController::class, 'store'])->name('store'); // 實際路徑為 POST /00_module_d/books
            // 編輯書籍的路由
            Route::get('/edit/{book:isbn}', [BookController::class, 'edit'])->name('edit'); // 實際路徑為 GET /00_module_d/books/exit/{book}
            Route::get('/show/{book:isbn}', [BookController::class, 'show'])->name('show'); // 實際路徑為 GET /00_module_d/books/show/{book}
            Route::put('/{book:isbn}', [BookController::class, 'update'])->name('update');  // 實際路徑為 PUT /00_module_d/books/{book}
            // 刪除書籍的路由
            Route::delete('/{book:isbn}', [BookController::class, 'destroy'])->name('destroy'); // 實際路徑為 DELETE /00_module_d/books/{book}
        });

        // ==========================================
        // 超級管理員專屬路由
        // ==========================================
        Route::middleware(CheckAdmin::class)->group(function () {
            // 出版社相關路由
            Route::prefix('publishers')->name('publishers.')->group(function () {
                // 顯示出版社列表的路由
                Route::get('/', [PublisherController::class, 'index'])->name('index'); // 實際路徑為 GET /00_module_d/publishers
                Route::get('/deactivate', [PublisherController::class, 'deactivate'])->name('deactivate'); // 實際路徑為 GET /00_module_d/publishers/deactivate/{publisher}
                // 新增出版社的路由
                Route::get('/new', [PublisherController::class, 'create'])->name('create'); // 實際路徑為 GET /00_module_d/publishers/create
                Route::post('/', [PublisherController::class, 'store'])->name('store'); // 實際路徑為 POST /00_module_d/publishers
                // 編輯出版社的路由
                Route::get('/edit/{publisher}', [PublisherController::class, 'edit'])->name('edit'); // 實際路徑為 GET /00_module_d/publishers/{publisher}
                Route::put('/{publisher}', [PublisherController::class, 'update'])->name('update'); // 實際路徑為 PUT /00_module_d/publishers/{publisher}
                Route::get('/activate/{publisher}', [PublisherController::class, 'show'])->name('activate'); // 實際路徑為 GET /00_module_d/publishers/activate/{publisher}
            });

            // 出版社管理員相關路由
            Route::prefix('managers')->name('managers.')->group(function () {
                // 顯示出版社管理員列表的路由
                Route::get('/', [AuthController::class, 'managers'])->name('index'); // 實際路徑為 GET /00_module_d/managers
                // 新增出版社管理員的路由
                Route::get('/new', [AuthController::class, 'create'])->name('create'); // 實際路徑為 GET /00_module_d/managers/create
                Route::post('/', [AuthController::class, 'store'])->name('store'); // 實際路徑為 POST /00_module_d/managers
                // 編輯出版社管理員的路由
                Route::get('/edit/{manager}', [AuthController::class, 'edit'])->name('edit'); // 實際路徑為 GET /00_module_d/managers/{manager}
                Route::put('/{manager}', [AuthController::class, 'update'])->name('update'); // 實際路徑為 PUT /00_module_d/managers/{manager}
                // 刪除出版社管理員的路由
                Route::delete('/{manager}', [AuthController::class, 'destroy'])->name('destroy'); // 實際路徑為 DELETE /00_module_d/managers/{manager}
            });
        });
    });
});
