# 練習順序

> 以下是依照競賽推薦的順序去建立
>

## 初始化檔案與安裝 api.php

```bash
php artisan install:api
```

Laravel 11+ 預設沒有 `routes/api.php`，跑這個指令會自動建立它、註冊進 `bootstrap/app.php`，順便會裝 Sanctum（這題用不到 Sanctum 的驗證機制，但裝著沒差，`personal_access_tokens` migration 就是它順手建的）。

## 建立 middleware 與不存在的 route 404 判斷

```bash
php artisan make:middleware CheckToken
php artisan make:middleware CheckAdmin
```

> app\Http\Middleware\CheckToken.php
>

```php
class CheckToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // [401] 沒帶 X-Authorization
        $authHeader = $request->header('X-Authorization');
        if (!$authHeader) {
            return response()->json(['success' => false, 'message' => 'Access Token is required'], 401);
        }

        // 題目格式是 "Bearer <token>"，把 "Bearer " 抹掉拿到純 token
        $token = str_replace('Bearer ', '', $authHeader);

        $user = User::where('token', $token)->first();

        // [401] 查無此 token
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid Access Token'], 401);
        }

        // [403] 帳號被封鎖
        if ($user->is_banned) {
            return response()->json(['success' => false, 'message' => 'User is banned'], 403);
        }

        // 把找到的 user 塞進 Request，後面的 Controller 用 $request->input('current_user') 直接拿，不用重撈
        $request->merge(['current_user' => $user]);

        return $next($request);
    }
}
```

> app\Http\Middleware\CheckAdmin.php
>

```php
class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 放在 CheckToken 底下用，這裡一定拿得到 current_user
        $user = $request->input('current_user');

        // [403] 非 admin 角色
        if (!$user || $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }

        return $next($request);
    }
}
```

> routes\api.php
>

```php
Route::middleware([CheckToken::class])->group(function () {
    // 需要登入才能用的 API 放這裡

    Route::middleware([CheckAdmin::class])->group(function () {
        // 需要 admin 角色才能用的 API 疊在 CheckToken 裡面
    });
});

// 放在整個檔案最後面：抓所有沒對到的路由，回題目要求的 404 格式
Route::any('{any}', function () {
    return response()->json(['success' => false, 'message' => 'Not Found'], 404);
})->where('any', '.*');
```

## 1. 使用者登入 (POST /api/login)

> Laravel 內建已經有 `User` model 與 `users` migration，不用另外 `make:model`，直接改預設檔案即可。欄位命名對齊 [module_c_db.sql](module_c_db.sql)：主鍵是 `user_id`、密碼欄位是 `password_hash`、token 欄位是 `token`。
>

> database\migrations\0001_01_01_000000_create_users_table.php
>

```php
public function up(): void
{
    // 對齊 module_c_db.sql 的 users 表命名
    Schema::create('users', function (Blueprint $table) {
        $table->id('user_id');

        $table->string('username')->unique();       // 題目登入、註冊皆使用 username
        $table->string('email')->unique();          // 題目要求的 email 欄位
        $table->string('password_hash');            // 密碼（雜湊後）
        $table->enum('role', ['admin', 'publisher', 'user'])->default('user'); // 角色
        $table->boolean('is_banned')->default(false); // 是否被封鎖
        $table->string('token')->nullable();         // 存 MD5 token 的欄位

        $table->timestamps();
    });
}
```

> app\Models\User.php
>

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 對齊 module_c_db.sql：主鍵欄位是 user_id，不是 Laravel 預設的 id
    protected $primaryKey = 'user_id';

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed', // 存入時自動雜湊，不用手動 Hash::make()
        ];
    }
}
```

> app\Http\Controllers\AuthController.php
>

```php
// 1. 使用者登入 (POST /api/login)
public function login(Request $request)
{
    $username = $request->input('username');
    $password = $request->input('password');

    // 尋找使用者
    $user = User::where('username', $username)->first();

    // [400] 帳號不存在或密碼錯誤 → 統一回同一個錯誤，避免洩漏帳號是否存在
    if (!$user || !Hash::check($password, $user->password_hash)) {
        return response()->json(['success' => false, 'message' => 'Login failed'], 400);
    }

    // [403] 帳密正確，但帳號被封鎖 → 順序一定要在帳密驗證「之後」
    if ($user->is_banned) {
        return response()->json(['success' => false, 'message' => 'User is banned'], 403);
    }

    // 題目核心規則：Token = 帳號 md5 後轉全小寫十六進位（同一人每次登入 token 都一樣）
    $token = strtolower(md5($username));

    $user->token = $token;
    $user->save();

    return response()->json([
        'success' => true,
        'data' => [
            'token' => $token,
            'user' => [
                'id'         => $user->user_id,
                'username'   => $user->username,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ]
        ]
    ]);
}
```

> routes\api.php
>

```php
// 最外層引用用到的 Controller
use App\Http\Controllers\AuthController;

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
```

**確認結果：邏輯正確。** 帳密錯誤 400、帳密對但被封鎖 403（順序正確，不會洩漏帳號是否存在）、token 產生方式、回傳欄位順序都符合題目要求，寫法維持「一欄一欄賦值」的最簡形式。

## 2. 使用者註冊 (POST /api/register)

> app\Http\Controllers\AuthController.php
>

```php
// 2. 使用者註冊 (POST /api/register)
public function register(Request $request)
{
    $username = $request->input('username');
    $email = $request->input('email');
    $password = $request->input('password');

    // [400] 缺少必要欄位
    if (empty($username) || empty($email) || empty($password)) {
        return response()->json(['success' => false, 'message' => 'Validation failed'], 400);
    }

    // [409] 使用者名稱已被使用
    if (User::where('username', $username)->exists()) {
        return response()->json(['success' => false, 'message' => 'Username already taken'], 409);
    }

    // [409] 郵件已被使用
    if (User::where('email', $email)->exists()) {
        return response()->json(['success' => false, 'message' => 'Email already taken'], 409);
    }

    $user = new User();
    $user->username      = $username;
    $user->email         = $email;
    $user->password_hash = $password; // User model 有 'password_hash' => 'hashed' cast，存入時自動雜湊，不用手動 Hash::make()
    $user->role          = 'user'; // 預設都是一般使用者
    $user->is_banned     = false;
    $user->save();

    return response()->json([
        'success' => true,
        'data' => [
            'user' => [
                'id'         => $user->user_id,
                'username'   => $user->username,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ]
        ]
    ], 201);
}
```

> routes\api.php
>

```php
// 公開 API，緊接在 /login 下面
Route::post('/register', [AuthController::class, 'register']);
```

**簡化紀錄：** 密碼靠 `password_hash` 欄位的 `'hashed'` cast 自動雜湊，不用手動 `Hash::make()`。使用者名稱／Email 是否重複的檢查用 `->exists()`（只問有沒有，不用撈整筆資料）。

## 9. 使用者登出 (POST /api/logout)

> app\Http\Controllers\AuthController.php
>

```php
// 9. 使用者登出 (POST /api/logout)
public function logout(Request $request)
{
    // 錯誤判定（沒帶 token / token 無效）都在 CheckToken 這個 middleware 做完了，這裡只要清 token 就好
    $user = $request->input('current_user');

    $user->token = null;
    $user->save();

    return response()->json(['success' => true]);
}
```

> routes\api.php
>

```php
Route::middleware([CheckToken::class])->group(function () {
    // 加入以下 Route
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

**簡化紀錄：** `CheckToken` middleware 找不到使用者時已經直接回 401、不會放行到這支方法，所以 `current_user` 一定存在，不用再包一層 `if ($user)` 判斷。

## 12. 取得所有使用者 (GET /api/users)

> app\Http\Controllers\AdminController.php
>

```php
// 12. 取得所有使用者 (GET /api/users)
public function users(Request $request)
{
    $limit = $request->query('limit', 10);
    $cursor = $request->query('cursor');
    $lastId = 0; // 預設從 id = 0 開始撈

    // [400] limit 不是數字，或超出 1~100
    if ($limit !== null && (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > 100)) {
        return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
    }

    // 解析 cursor：base64 → JSON → 取出 id，三種爛 cursor 都擋下來
    if ($cursor) {
        $decodedBase64 = base64_decode($cursor, true);
        $cursorData = json_decode($decodedBase64);

        if ($decodedBase64 === false || !$cursorData || !isset($cursorData->id)) {
            return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
        }

        $lastId = $cursorData->id;
    }

    // 多撈 1 筆，用來判斷還有沒有下一頁；排序、篩選都用 user_id（User 的主鍵）
    $users = User::where('role', 'user')->where('user_id', '>', $lastId)->orderBy('user_id', 'asc')->take($limit + 1)->get();

    $hasNextPage = $users->count() > $limit;
    if ($hasNextPage) {
        $users = $users->take($limit);
    }

    $nextCursor = null;
    if ($hasNextPage && $users->isNotEmpty()) {
        $nextCursor = base64_encode(json_encode(['id' => $users->last()->user_id]));
    }

    $prevCursor = null;
    if ($lastId > 0 && $users->isNotEmpty()) {
        $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
    }

    return response()->json([
        'success' => true,
        'data' => $users->map(fn($user) => [
            'id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_banned' => (bool) $user->is_banned,
            'created_at' => $user->created_at->toISOString(),
        ]),
        'meta' => [
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::get('/users', [AdminController::class, 'users']);
});
```

## 13. 更新使用者角色 (PUT /api/users/{user_id})

> app\Http\Controllers\AdminController.php
>

```php
// 13. 更新使用者角色 (PUT /api/users/{user_id})
public function update(Request $request, $user_id)
{
    // [404]（User::find 會自動用 user_id 這個主鍵去查，不用改寫法）
    $user = User::find($user_id);
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    // [409] 被封鎖的使用者角色不得更新
    if ($user->is_banned) {
        return response()->json(['success' => false, 'message' => 'Banned user update failed'], 409);
    }

    // [403] 系統必須至少保留一位管理員：目前是 admin、且要被改成非 admin 時才檢查
    if ($user->role === 'admin' && $request->input('role') !== 'admin') {
        $adminCount = User::where('role', 'admin')->count();

        if ($adminCount <= 1) {
            return response()->json(['success' => false, 'message' => 'Last admin demotion forbidden'], 403);
        }
    }

    $user->role = $request->input('role');
    $user->save();

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_banned' => (bool) $user->is_banned,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::put('/users/{user_id}', [AdminController::class, 'update']);
});
```

## 14. 封鎖使用者 (PUT /api/users/{user_id}/ban)

> app\Http\Controllers\AdminController.php
>

```php
// 14. 封鎖使用者 (PUT /api/users/{user_id}/ban)
public function ban(Request $request, $user_id)
{
    // [400] 不能封鎖自己
    $current_user = $request->input('current_user');
    if ($current_user->user_id === (int) $user_id) {
        return response()->json(['success' => false, 'message' => 'Cannot ban self'], 400);
    }

    // [404]
    $user = User::find($user_id);
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    // [403] 無法封鎖另一位管理員
    if ($user->role === 'admin') {
        return response()->json(['success' => false, 'message' => 'Cannot ban another admin'], 403);
    }

    $user->is_banned = true;
    $user->save();

    // 注意：規格範例回應不含 created_at
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_banned' => (bool) $user->is_banned,
            'updated_at' => $user->updated_at->toISOString(),
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::put('/users/{user_id}/ban', [AdminController::class, 'ban']);
});
```

## 15. 解除封鎖使用者 (PUT /api/users/{user_id}/unban)

> app\Http\Controllers\AdminController.php
>

```php
// 15. 解除封鎖使用者 (PUT /api/users/{user_id}/unban)
public function unban(Request $request, $user_id)
{
    // [404]
    $user = User::find($user_id);
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    $user->is_banned = false;
    $user->save();

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_banned' => (bool) $user->is_banned,
            'updated_at' => $user->updated_at->toISOString(),
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::put('/users/{user_id}/unban', [AdminController::class, 'unban']);
});
```

## 16. 創建新專輯 (POST /api/albums)

```bash
php artisan make:model Album -m --api
```

> database\migrations\xx_xx_xx_create_albums_table.php
>

```php
public function up(): void
{
    // 對齊 module_c_db.sql 的 albums 表命名（deleted_at 是 SQL 沒有的，但題目規格第 18 題要求軟刪除，所以保留）
    Schema::create('albums', function (Blueprint $table) {
        $table->id('album_id');

        $table->foreignId('publisher_id')->constrained('users', 'user_id'); // 參考 users 表的 user_id 欄位
        $table->string('title'); // 專輯名稱
        $table->string('artist'); // 藝術家名稱
        $table->integer('release_year'); // 發行年份
        $table->string('genre'); // 音樂類型
        $table->text('description')->nullable(); // 專輯描述
        $table->softDeletes(); // 支援軟刪除（題目規格要求，SQL 參考檔沒有這欄）

        $table->timestamps();
    });
}
```

> app\Models\Album.php
>

```php
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 引入 BelongsTo 類別以便定義關聯

class Album extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 對齊 module_c_db.sql：主鍵欄位是 album_id，不是 Laravel 預設的 id
    protected $primaryKey = 'album_id';

    // 定義關聯：這張專輯屬於哪一個發布的管理員（User 的主鍵是 user_id，要明講）
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id', 'user_id');
    }
}
```

> app\Http\Controllers\AlbumController.php
>

```php
// 16. 創建新專輯 (POST /api/albums)
public function store(Request $request)
{
    // 1. 取得目前登入的使用者資料 (從 Middleware 傳進來的)
    $current_user = $request->input('current_user');

    // 2. 建立一個全新的空專輯物件
    $album = new Album();

    // 3. 一對一指派欄位資料
    $album->publisher_id = $current_user->user_id; // 將建立者設定為當前登入的管理員 ID
    $album->title        = $request->input('title');
    $album->artist       = $request->input('artist');
    $album->release_year = (int) $request->input('release_year'); // 強制轉成整數符合型態
    $album->genre        = $request->input('genre');
    $album->description  = $request->input('description');

    // 4. 儲存進資料庫
    $album->save();

    // 5. [201 成功] 回傳符合題目要求的 JSON 格式與 201 狀態碼
    return response()->json([
        'success' => true,
        'data' => [
            'id'           => $album->album_id,
            'title'        => $album->title,
            'artist'       => $album->artist,
            'release_year' => $album->release_year,
            'genre'        => $album->genre,
            'description'  => $album->description,
            'publisher'    => [
                'id'       => $current_user->user_id,
                'username' => $current_user->username,
                'email'    => $current_user->email,
            ],
            'created_at'   => $album->created_at->toISOString(), // 時間格式帶 Z
            'updated_at'   => $album->updated_at->toISOString(), // 時間格式帶 Z
        ]
    ], 201);
}
```

> routes\api.php
>

```php
// 最外層引用用到的 Controller
use App\Http\Controllers\AlbumController;

// ==========================================
// 3. 管理員專屬 API (使用寫好的 CheckAdmin)
// ==========================================
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::post('/albums', [AlbumController::class, 'store']);
});
```

## 17. 更新專輯訊息 (PUT /api/albums/{album_id})

> app\Http\Controllers\AlbumController.php
>

```php
// 17. 更新專輯訊息 (PUT /api/albums/{album_id})
public function update(Request $request, $album_id)
{
    // [404]
    $album = Album::find($album_id);
    if (!$album) {
        return response()->json(['success' => false, 'message' => 'Not Found'], 404);
    }

    $album->title       = $request->input('title');
    $album->description = $request->input('description');
    $album->save();

    $publisher = $album->publisher;

    return response()->json([
        'success' => true,
        'data' => [
            'id'           => $album->album_id,
            'title'        => $album->title,
            'artist'       => $album->artist,
            'release_year' => $album->release_year,
            'genre'        => $album->genre,
            'description'  => $album->description,
            'publisher'    => [
                'id'       => $publisher->user_id,
                'username' => $publisher->username,
                'email'    => $publisher->email,
            ],
            'created_at'   => $album->created_at->toISOString(),
            'updated_at'   => $album->updated_at->toISOString(),
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::put('/albums/{album_id}', [AlbumController::class, 'update']);
});
```

## 18. 刪除專輯 (DELETE /api/albums/{album_id})

此刪除為軟刪除，靠 [Album.php](../app/Models/Album.php) 的 `SoftDeletes` trait 處理，`->delete()` 實際上是 UPDATE `deleted_at`。

> app\Http\Controllers\AlbumController.php
>

```php
// 18. 刪除專輯 (DELETE /api/albums/{album_id})
public function destroy($album_id)
{
    // [404]
    $album = Album::find($album_id);
    if (!$album) {
        return response()->json(['success' => false, 'message' => 'Not Found'], 404);
    }

    $album->delete(); // 軟刪除

    return response()->json(['success' => true], 200);
}
```

> routes\api.php
>

```php
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
    Route::delete('/albums/{album_id}', [AlbumController::class, 'destroy']);
});
```

## 3. 取得所有專輯 (GET /api/albums)

> app\Http\Controllers\AlbumController.php
>

```php
// 3. 取得所有專輯 (GET /api/albums)
public function index(Request $request)
{
    $limit = $request->query('limit', 10);
    $cursor = $request->query('cursor');
    $filter = $request->query('filter');
    $yearRange = $request->query('year');
    $lastId = 0;

    // [400] limit 檢查
    if ($limit !== null && (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > 100)) {
        return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
    }

    // 解析 cursor（跟 GET /api/users 同一套邏輯）
    if ($cursor) {
        $decodedBase64 = base64_decode($cursor, true);
        $cursorData = json_decode($decodedBase64);

        if ($decodedBase64 === false || !$cursorData || !isset($cursorData->id)) {
            return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
        }

        $lastId = $cursorData->id;
    }

    // with('publisher') 做 Eager Loading，避免 N+1；排序、篩選都用 album_id（Album 的主鍵）
    $query = Album::with('publisher')->where('album_id', '>', $lastId);

    // filter=A → title 要 A 開頭
    if (!empty($filter)) {
        $query->where('title', 'like', $filter . '%');
    }

    // year 支援單一年份或 "1980-2000" 區間
    if (!empty($yearRange)) {
        if (!str_contains($yearRange, '-')) {
            return response()->json(['success' => false, 'message' => 'Invalid year format'], 400);
        }

        $years = explode('-', $yearRange);

        if (count($years) !== 2 || !is_numeric($years[0]) || !is_numeric($years[1])) {
            return response()->json(['success' => false, 'message' => 'Invalid year format'], 400);
        }

        $startYear = (int) $years[0];
        $endYear = (int) $years[1];

        if ($startYear > $endYear) {
            return response()->json(['success' => false, 'message' => 'Invalid year format'], 400);
        }

        $query->whereBetween('release_year', [$startYear, $endYear]);
    }

    $albums = $query->orderBy('album_id', 'asc')->take($limit + 1)->get();

    $hasNextPage = $albums->count() > $limit;
    if ($hasNextPage) {
        $albums = $albums->take($limit);
    }

    $nextCursor = null;
    if ($hasNextPage && $albums->isNotEmpty()) {
        $nextCursor = base64_encode(json_encode(['id' => $albums->last()->album_id]));
    }

    $prevCursor = null;
    if ($lastId > 0 && $albums->isNotEmpty()) {
        $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
    }

    return response()->json([
        'success' => true,
        'data' => $albums->map(fn($album) => [
            'id'           => $album->album_id,
            'title'        => $album->title,
            'artist'       => $album->artist,
            'release_year' => $album->release_year,
            'publisher'    => [
                'id'       => $album->publisher->user_id,
                'username' => $album->publisher->username,
                'email'    => $album->publisher->email,
            ],
        ]),
        'meta' => [
            'prev_cursor' => $prevCursor,
            'next_cursor' => $nextCursor
        ]
    ], 200);
}
```

> routes\api.php
>

```php
// 公開 API
Route::get('/albums', [AlbumController::class, 'index']);
```

## 4. 取得專輯資訊 (GET /api/albums/{album_id})

> app\Http\Controllers\AlbumController.php
>

```php
// 4. 取得專輯資訊 (GET /api/albums/{album_id})
public function show($album_id)
{
    // [404]（找不到，或已被軟刪除）
    $album = Album::find($album_id);
    if (!$album) {
        return response()->json(['success' => false, 'message' => 'Not Found'], 404);
    }

    $publisher = $album->publisher;

    return response()->json([
        'success' => true,
        'data' => [
            'id'           => $album->album_id,
            'title'        => $album->title,
            'artist'       => $album->artist,
            'release_year' => $album->release_year,
            'genre'        => $album->genre,
            'description'  => $album->description,
            'created_at'   => $album->created_at->toISOString(),
            'updated_at'   => $album->updated_at->toISOString(),
            'publisher'    => [
                'id'       => $publisher->user_id,
                'username' => $publisher->username,
                'email'    => $publisher->email,
            ],
        ]
    ], 200);
}
```

> routes\api.php
>

```php
// 公開 API
Route::get('/albums/{album_id}', [AlbumController::class, 'show']);
```

## 19.新增歌曲到專輯 (POST /api/albums/{album_id}/songs)

```jsx
php artisan make:model Song -m --api
```

> database\migrations\xx_xx_xx_183304_create_songs_table.php
>

```php
public function up(): void
{
    // 對齊 module_c_db.sql 的 songs 表命名；曲風標籤改用 labels + song_labels 關聯表，不再用 JSON 欄位
    Schema::create('songs', function (Blueprint $table) {
        $table->id('song_id');

        // 關聯到專輯表，如果專輯被刪除，底下的歌曲也一併連帶刪除 (Cascade)
        $table->foreignId('album_id')->constrained('albums', 'album_id')->onDelete('cascade');
        $table->string('title');
        $table->integer('duration_seconds');
        $table->text('lyrics')->nullable();
        $table->integer('track_order'); // SQL 用 track_order，避開 order 這個 MySQL 保留字
        $table->integer('view_count')->default(0); // 題目要求預設為 0，SQL 沒有這欄但統計 API 需要，保留

        $table->boolean('is_cover')->default(false);

        // 存圖片在 storage 的實體路徑
        $table->string('cover_image_path')->nullable();

        $table->softDeletes(); // 支援軟刪除（SQL 有這欄，一致）

        $table->timestamps();
    });
}
```

曲風標籤額外建兩張表（對齊 module_c_db.sql 的 `labels` + `song_labels`），8 個預設值直接在 migration 裡塞資料，不用另外寫 seeder：

```bash
php artisan make:migration create_labels_table
php artisan make:migration create_song_labels_table
```

> database\migrations\xx_xx_xx_190000_create_labels_table.php
>

```php
public function up(): void
{
    Schema::create('labels', function (Blueprint $table) {
        $table->id('label_id');
        $table->string('name');
    });

    // 8 個預設曲風固定用英文（跟題目統計 API 範例 labels=Pop,Rock 對齊）
    DB::table('labels')->insert([
        ['label_id' => 1, 'name' => 'Pop'],
        ['label_id' => 2, 'name' => 'Rock'],
        ['label_id' => 3, 'name' => 'Hip-Hop'],
        ['label_id' => 4, 'name' => 'Electronic'],
        ['label_id' => 5, 'name' => 'Jazz'],
        ['label_id' => 6, 'name' => 'Classical'],
        ['label_id' => 7, 'name' => 'Chill'],
        ['label_id' => 8, 'name' => 'Country'],
    ]);
}
```

> database\migrations\xx_xx_xx_190100_create_song_labels_table.php
>

```php
public function up(): void
{
    Schema::create('song_labels', function (Blueprint $table) {
        $table->id('song_label_id');
        $table->foreignId('song_id')->constrained('songs', 'song_id')->onDelete('cascade');
        $table->foreignId('label_id')->constrained('labels', 'label_id')->onDelete('cascade');
    });
}
```

> app\Models\Label.php（新檔案）
>

```php
class Label extends Model
{
    protected $primaryKey = 'label_id';
    public $timestamps = false; // 這張表沒有 created_at / updated_at

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'song_labels', 'label_id', 'song_id', 'label_id', 'song_id');
    }
}
```

> app\Models\Song.php
>

```php
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Song extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 對齊 module_c_db.sql：主鍵欄位是 song_id，不是 Laravel 預設的 id
    protected $primaryKey = 'song_id';

    protected $casts = [
        'is_cover' => 'boolean', // 強制轉成布林值
    ];

    // 動態屬性：自動組裝題目要求的 cover_image_url，以及把關聯表組回題目要求的 label 陣列格式
    protected $appends = ['cover_image_url', 'label'];

    public function getCoverImageUrlAttribute()
    {
        return "/api/songs/{$this->song_id}/cover";
    }

    // 曲風標籤改用 labels + song_labels 關聯表（對齊 SQL），不再用 JSON 欄位存
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'song_labels', 'song_id', 'label_id', 'song_id', 'label_id');
    }

    // 題目要求的回傳格式是純字串陣列，例如 ["Rock", "Pop"]，所以把關聯撈出來的 Label 轉成純名稱陣列
    public function getLabelAttribute()
    {
        return $this->labels->pluck('name')->values()->all();
    }
}
```

> app\Http\Controllers\SongController.php
>

```php
// 19.新增歌曲到專輯 (POST /api/albums/{album_id}/songs)
public function store(Request $request, $album_id)
{
  // 1. [404 檢查]
  $album = Album::find($album_id);
  if (!$album) {
      return response()->json(['success' => false, 'message' => 'Not Found'], 404);
  }

  // 2. [400 驗證] 基本欄位檢查
  if (!$request->has('title') || !$request->has('duration_seconds')) {
      return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
  }

  // 3. 題目規範：驗證是否屬於這 8 大英文預設標籤（對齊 module_c_db.sql 的 labels 表）
  $allowedLabels = ['Pop', 'Rock', 'Hip-Hop', 'Electronic', 'Jazz', 'Classical', 'Chill', 'Country'];
  $finalLabels = [];

  if ($request->has('label') && !empty($request->input('label'))) {
      // 依逗號拆開
      $inputTags = explode(',', $request->input('label'));

      foreach ($inputTags as $tag) {
          $trimmedTag = trim($tag);

          // 不在 8 大預設標籤裡就噴 400
          if (!in_array($trimmedTag, $allowedLabels)) {
              return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
          }

          if (!in_array($trimmedTag, $finalLabels)) {
              $finalLabels[] = $trimmedTag;
          }
      }
  }

  // 4. 處理實體圖片上傳
  $imagePath = null;
  if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
      $imagePath = $request->file('cover_image')->store('covers');
  }

  // 算一下目前這張專輯有幾首歌，直接 +1。這樣就算資料庫沒給預設值也不會爆掉！
  $currentSongsCount = Song::where('album_id', $album->album_id)->count();
  $nextOrder = $currentSongsCount + 1;

  // 5. 寫入資料庫
  $song = new Song();
  $song->album_id         = $album->album_id;
  $song->title            = $request->input('title');
  $song->duration_seconds = (int)$request->input('duration_seconds');
  $song->lyrics           = $request->input('lyrics');
  $song->track_order      = $nextOrder;
  $song->view_count       = 0;
  $song->is_cover         = $request->boolean('is_cover'); // Laravel 會自動把 "true"/"false" 字串轉成 boolean
  $song->cover_image_path = $imagePath;
  $song->save();

  // 曲風標籤改存關聯表：查出名稱對應的 label_id，用 sync() 寫進 song_labels
  if (!empty($finalLabels)) {
      $song->labels()->sync(Label::whereIn('name', $finalLabels)->pluck('label_id'));
  }

  // 6. [201 Created] 回傳
  return response()->json([
      'success' => true,
      'data' => [
          'id'               => $song->song_id,
          'album_id'         => $song->album_id,
          'title'            => $song->title,
          'duration_seconds' => $song->duration_seconds,
          'lyrics'           => $song->lyrics,
          'order'            => $song->track_order,
          'view_count'       => $song->view_count,
          'label'            => $song->label, // 由 Song::getLabelAttribute() 從關聯表組成純字串陣列
          'is_cover'         => $song->is_cover,
          'cover_image_url'  => $song->cover_image_url,
          'created_at'       => $song->created_at->toISOString(),
          'updated_at'       => $song->updated_at->toISOString(),
      ]
  ], 201);
}
```

> routes\api.php
>

```php
// 最外層引用用到的 Controller
use App\Http\Controllers\SongController;

// ==========================================
// 3. 管理員專屬 API (使用寫好的 CheckAdmin)
// ==========================================
Route::middleware([CheckAdmin::class])->group(function () {
    // 加入以下 Route
		Route::post('/albums/{album_id}/songs', [SongController::class, 'store']);
});
```

## 8.取得歌曲封面圖片 (GET /api/songs/{song_id}/cover)

> app\Http\Controllers\SongController.php
>

```php
// 8.取得歌曲封面圖片 (GET /api/songs/{song_id}/cover)
public function showCover($song_id)
{
    // 1. 尋找歌曲（Song::find 會自動用 song_id 這個主鍵去查）
    $song = Song::find($song_id);

    // [404 檢查] 找不到歌曲，或該歌曲根本沒上傳過圖片路徑
    if (!$song || !$song->cover_image_path) {
        return response()->json(['success' => false, 'message' => 'Cover Not Found'], 404);
    }

    // 2. 取得實體檔案的絕對路徑，19 題會把圖片路徑存在 storage 裡。
    // 註：Laravel 11+ 預設 store() 會存在 storage/app/private/
    $filePath = storage_path('app/private/' . $song->cover_image_path);

    // 檢查硬碟裡是不是真的有這個檔案
    if (!file_exists($filePath)) {
        return response()->json(['success' => false, 'message' => 'File Not Found'], 404);
    }

    // 3. 把實體圖檔轉成二進位流吐給前端（瀏覽器會直接顯示成圖片）
    return response()->file($filePath, [
        'Content-Type' => 'image/jpeg' // 確保對齊題目要求的 image/jpeg
    ]);
}
```

> routes\api.php
>

```php
// 最外層引用用到的 Controller
use App\Http\Controllers\SongController;

// ==========================================
// 1. 公開 API (訪客不用 Token 就能呼叫)
// ==========================================

// 加入以下 Route
Route::get('/songs/{song_id}/cover', [SongController::class, 'showCover']);
```

## 待完成清單（由簡入繁排序）

> 尚未實作／尚未在 routes\api.php 註冊的 8 支，依複雜度由簡到繁排列如下，每完成一支再補上對應內容。

## 6. 取得專輯內歌曲 (GET /api/albums/{album_id}/songs)

`SongController` 建立時 Laravel 就先生成了一個空的 `index()` 方法（RESTful 慣例），直接改寫成這支即可，不用另外新增方法。

> app\Http\Controllers\SongController.php
>

```php
// 6. 取得專輯內歌曲 (GET /api/albums/{album_id}/songs)
public function index($album_id)
{
    // [404]
    $album = Album::find($album_id);
    if (!$album) {
        return response()->json(['success' => false, 'message' => 'Not Found'], 404);
    }

    // with('labels') 預先撈好標籤關聯，避免 map 裡面每首歌都各查一次 (N+1)
    $songs = Song::with('labels')->where('album_id', $album->album_id)->orderBy('track_order', 'asc')->get();

    return response()->json([
        'success' => true,
        'data' => $songs->map(fn($song) => [
            'id'               => $song->song_id,
            'album_id'         => $song->album_id,
            'title'            => $song->title,
            'label'            => $song->label,
            'duration_seconds' => $song->duration_seconds,
            'order'            => $song->track_order,
            'is_cover'         => $song->is_cover,
            'cover_image_url'  => $song->cover_image_url,
        ]),
    ], 200);
}
```

> routes\api.php
>

```php
// 公開 API，跟 GET /albums/{album_id} 放一起
Route::get('/albums/{album_id}/songs', [SongController::class, 'index']);
```

**驗證：** 起了本機伺服器實測，建立 2 首歌（`Song One` label=Rock、`Song Two` label=Pop,Jazz）後打這支，回傳依 `track_order` 正確排序、`label` 陣列正確組出多個標籤；查不存在的 `album_id` 回 404。測完把資料庫 `migrate:fresh --seed` 重置回乾淨狀態。

## 7. 取得所有歌曲 (GET /api/songs)

跟 [3. 取得所有專輯](#3-取得所有專輯-get-apialbums) 用一樣的 cursor 分頁邏輯，多加一個 `keyword` 依歌名篩選。多了 `album_title` 欄位，所以 [Song.php](../app/Models/Song.php) 補上 `album()` 關聯。

> app\Models\Song.php（新增這段關聯）
>

```php
// 定義關聯：這首歌屬於哪一張專輯（GET /api/songs 要回傳 album_title 會用到）
public function album(): BelongsTo
{
    return $this->belongsTo(Album::class, 'album_id', 'album_id');
}
```

> app\Http\Controllers\SongController.php
>

```php
// 7. 取得所有歌曲 (GET /api/songs)
public function all(Request $request)
{
    $limit = $request->query('limit', 10);
    $cursor = $request->query('cursor');
    $keyword = $request->query('keyword');
    $lastId = 0;

    // [400] limit 不是數字，或超出 1~100
    if ($limit !== null && (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > 100)) {
        return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
    }

    // 解析 cursor（跟其他分頁 API 同一套邏輯）
    if ($cursor) {
        $decodedBase64 = base64_decode($cursor, true);
        $cursorData = json_decode($decodedBase64);

        if ($decodedBase64 === false || !$cursorData || !isset($cursorData->id)) {
            return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
        }

        $lastId = $cursorData->id;
    }

    // with(['labels', 'album']) 預先撈好標籤跟專輯名稱，避免 map 裡面每首歌都各查一次 (N+1)
    $query = Song::with(['labels', 'album'])->where('song_id', '>', $lastId);

    // keyword 只用來比對歌名
    if (!empty($keyword)) {
        $query->where('title', 'like', '%' . $keyword . '%');
    }

    $songs = $query->orderBy('song_id', 'asc')->take($limit + 1)->get();

    $hasNextPage = $songs->count() > $limit;
    if ($hasNextPage) {
        $songs = $songs->take($limit);
    }

    $nextCursor = null;
    if ($hasNextPage && $songs->isNotEmpty()) {
        $nextCursor = base64_encode(json_encode(['id' => $songs->last()->song_id]));
    }

    $prevCursor = null;
    if ($lastId > 0 && $songs->isNotEmpty()) {
        $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
    }

    return response()->json([
        'success' => true,
        'data' => $songs->map(fn($song) => [
            'id'               => $song->song_id,
            'album_id'         => $song->album_id,
            'title'            => $song->title,
            'label'            => $song->label,
            'duration_seconds' => $song->duration_seconds,
            'album_title'      => $song->album->title,
            'cover_image_url'  => $song->cover_image_url,
        ]),
        'meta' => [
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
        ]
    ], 200);
}
```

> routes\api.php
>

```php
Route::get('/songs', [SongController::class, 'all']);
```

**驗證：** 建立一張專輯（Love Album）跟兩首歌（Love Story / Other Song）後，`GET /api/songs` 兩首都正確回傳、`album_title` 正確帶出專輯名稱；`?keyword=love` 只篩出 `Love Story`；`?limit=abc` 正確回 400。測完把資料庫重置回乾淨狀態。

## 22. 自專輯刪除歌曲 (DELETE /api/albums/{album_id}/songs/{song_id})

## 10. 取得歌曲資訊 (GET /api/songs/{song_id})

## 21. 更新歌曲訊息 (POST /api/albums/{album_id}/songs/{song_id})

## 20. 更新歌曲順序 (PUT /api/albums/{album_id}/songs/order)

## 5. 取得專輯封面圖片 (GET /api/albums/{album_id}/cover)

## 11. 取得統計結果 (GET /api/statistics)
