<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    // 3. 取得所有專輯 (GET /api/albums)
    public function index(Request $request)
    {
        // --- 1. 處理基本參數 ---
        $limit = $request->query('limit', 10);
        $cursor = $request->query('cursor');
        $filter = $request->query('filter');
        $yearRange = $request->query('year');
        $lastId = 0;

        if ($limit !== null) {
            // 檢查條件：如果「不是數字」 或者 「數字小於 1」 或者 「數字大於 100」
            if (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > 100) {
                return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
            }
        }

        // --- 2. 驗證並解析 Cursor（沿用 GET /api/users 的分頁邏輯） ---
        if ($cursor) {
            $cursorData = json_decode(base64_decode($cursor, true));
            if (!$cursorData || !isset($cursorData->id)) {
                return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
            }
            $lastId = $cursorData->id;
        }

        // --- 3. 開始建立查詢（Query Builder） ---
        // 【重要】：因為題目要求回傳 publisher，我們在這裡加上 with('publisher')，
        // 這樣可以做到「預加載（Eager Loading）」，有效解決 N+1 問題，效能會非常好！
        $query = Album::with('publisher')->where('id', '>', $lastId);

        // 處理 filter 篩選 (例如 filter=A，代表 title 要 A 開頭)
        if (!empty($filter)) {
            $query->where('title', 'like', $filter . '%'); // MySQL 的 LIKE 'A%'
        }

        // 處理 year 區間篩選 (例如 year="1980-2000")
        if (!empty($yearRange)) {
            // 用 '-' 拆開成陣列
            $years = explode('-', $yearRange);

            if (count($years) === 2) {
                $startYear = (int)$years[0];
                $endYear = (int)$years[1];

                // 使用 whereBetween 來尋找區間
                $query->whereBetween('release_year', [$startYear, $endYear]);
            }
        }

        // --- 4. 撈出資料（先排序後多撈一筆來判斷有沒有下一頁） ---
        $albums = $query->orderBy('id', 'asc')->take($limit + 1)->get();

        // --- 5. 判斷並切除多撈的資料 ---
        $hasNextPage = $albums->count() > $limit;
        if ($hasNextPage) {
            $albums = $albums->take($limit);
        }

        // --- 6. 計算分頁游標（完全對齊你第 12 題的完美邏輯） ---
        $nextCursor = null;
        $prevCursor = null;

        if ($hasNextPage && $albums->isNotEmpty()) {
            $nextId = $albums->last()->id;
            $nextCursor = base64_encode(json_encode(['id' => $nextId]));
        }

        if ($lastId > 0 && $albums->isNotEmpty()) {
            $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
        }

        // --- 7. 組裝符合題目要求的 Response 格式 ---
        return response()->json([
            'success' => true,
            'data' => $albums->map(fn($album) => [
                'id'           => $album->id,
                'title'        => $album->title,
                'artist'       => $album->artist,
                'release_year' => $album->release_year,
                'publisher'    => [
                    'id'       => $album->publisher->id,
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

    // 16. 創建新專輯 (POST /api/albums)
    public function store(Request $request)
    {
        // 1. 取得目前登入的使用者資料 (從 Middleware 傳進來的)
        $current_user = $request->input('current_user');

        // 2. 建立一個全新的空專輯物件
        $album = new Album();

        // 3. 一對一指派欄位資料
        $album->publisher_id = $current_user->id; // 將建立者設定為當前登入的管理員 ID
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
                'id'           => $album->id,
                'title'        => $album->title,
                'artist'       => $album->artist,
                'release_year' => $album->release_year,
                'genre'        => $album->genre,
                'description'  => $album->description,
                'publisher'    => [
                    'id'       => $current_user->id,
                    'username' => $current_user->username,
                    'email'    => $current_user->email,
                ],
                'created_at'   => $album->created_at->toISOString(), // 時間格式帶 Z
                'updated_at'   => $album->updated_at->toISOString(), // 時間格式帶 Z
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Album $album)
    {
        //
    }

    // 17. 更新專輯訊息 (PUT /api/albums/{album_id})
    public function update(Request $request, $album_id)
    {
        // 1. [404 檢查] 確認資料庫是否有該專輯
        $album = Album::find($album_id);

        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 2. 手動指派更新的欄位
        $album->title       = $request->input('title');
        $album->description = $request->input('description');

        // 3. 儲存更新後的資料回資料庫
        $album->save();

        // 4. 取得該專輯的發布者資料 (利用我們定義好的 belongsTo 關聯)
        $publisher = $album->publisher;

        // 5. [200 成功] 回傳符合題目要求的 JSON 格式回應
        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $album->id,
                'title'        => $album->title,
                'artist'       => $album->artist,
                'release_year' => $album->release_year,
                'genre'        => $album->genre,
                'description'  => $album->description,
                'publisher'    => [
                    'id'       => $publisher->id,
                    'username' => $publisher->username,
                    'email'    => $publisher->email,
                ],
                'created_at'   => $album->created_at->toISOString(),
                'updated_at'   => $album->updated_at->toISOString(),
            ]
        ], 200);
    }

    // 18. 刪除專輯 (DELETE /api/albums/{album_id})
    public function destroy($album_id)
    {
        // 1. [404 檢查] 確認資料庫是否有該專輯
        $album = Album::find($album_id);

        // 如果找不到該專輯（或是它早就被軟刪除了），直接攔截並回傳 404
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 2. 執行軟刪除
        // 【提示】：因為 Model 有設定 SoftDeletes，這行在資料庫背後其實是執行 UPDATE，把 deleted_at 填上時間
        $album->delete();

        // 3. [200 成功] 回傳題目要求的 JSON 格式
        return response()->json([
            'success' => true
        ], 200);
    }
}
