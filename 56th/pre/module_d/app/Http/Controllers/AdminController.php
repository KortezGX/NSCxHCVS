<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// 先在最外層引用
use App\Models\User;

class AdminController extends Controller
{
    // 12. 取得所有使用者 (GET /api/users)
    public function users(Request $request)
    {
        // 取得 limit 參數，題目範例是 10，如果沒傳就預設給 10
        $limit = $request->query('limit', 10);

        // 處理 cursor 分頁判斷
        $cursor = $request->query('cursor');
        $lastId = 0; // 預設從 id = 0 開始撈

        // 如果前端有傳 cursor，就把它解開拿到裡面的 id
        if ($cursor) {
            $cursorData = json_decode(base64_decode($cursor, true)); // 加上 true，如果是無效的 cursor 會直接回傳 false
            // 【關鍵判斷】：如果解不開，或者解開後裡面沒有 id，就是 Invalid cursor！
            if (!$cursorData || !isset($cursorData->id)) {
                return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
            }
            // 檢查通過，順利拿到 id
            $lastId = $cursorData->id;
        }

        // 撈出身分為 user 的使用者，並抓出分頁 cursor 需要的資料，以及最多抓幾筆資料
        $users = User::where('role', 'user')->where('id', '>', $lastId)->orderBy('id', 'asc')->take($limit + 1)->get();

        // 判斷到底有沒有下一頁
        $hasNextPage = $users->count() > $limit;

        // 如果有下一頁，就把多撈的最後一筆切掉
        if ($hasNextPage) {
            $users = $users->take($limit);
        }

        // --- 開始計算分頁游標 ---
        $nextCursor = null;
        $prevCursor = null;

        // 1. 計算 Next Cursor
        // 條件：必須「有下一頁」而且「目前撈出的資料不是空的」
        if ($hasNextPage && $users->isNotEmpty()) {
            $nextId = $users->last()->id;
            $nextCursor = base64_encode(json_encode(['id' => $nextId]));
        }

        // 2. 計算 Prev Cursor
        // 條件：必須「不是第一頁($lastId > 0)」而且「目前撈出的資料不是空的」
        if ($lastId > 0 && $users->isNotEmpty()) {
            $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
        }

        // 回傳 JSON 結果
        return response()->json([
            'success' => true,
            'data' => $users->map(fn ($user) => [
                'id' => $user->id,
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
}
