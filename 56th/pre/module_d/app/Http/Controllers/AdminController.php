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
            'data' => $users->map(fn($user) => [
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

    // 13. 更新使用者角色 (PUT /api/users/{user_id})
    public function update(Request $request, $user_id)
    {
        // 1. [404] 檢查使用者是否存在
        $user = User::find($user_id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // 2. [409] 檢查該使用者是否已被封鎖 (is_banned)
        if ($user->is_banned) {
            return response()->json(['success' => false, 'message' => 'Banned user update failed'], 409);
        }

        // 3. [403] 檢查是否為最後一位管理員降級
        // 觸發條件：目前使用者是 admin，且企圖改為非 admin 角色
        if ($user->role === 'admin' && $request->input('role') !== 'admin') {

            // 計算全系統目前的 admin 總數
            $adminCount = User::where('role', 'admin')->count();

            if ($adminCount <= 1) {
                return response()->json(['success' => false, 'message' => 'Last admin demotion forbidden'], 403);
            }
        }

        // 4. 通過所有檢查，執行更新
        $user->role = $request->input('role');
        $user->save();

        // 5. [200] 回傳成功回應
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_banned' => (bool) $user->is_banned,
                // 確保時間格式符合 ISO 8601 帶 Z
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ]
        ], 200);
    }

    // 14. 封鎖使用者 (PUT /api/users/{user_id}/ban)
    public function ban(Request $request, $user_id)
    {
        // 1. [400 檢查] 不能封鎖自己
        // 先取得目前登入的使用者資料
        $current_user = $request->input('current_user');

        if ($current_user->id === (int) $user_id) {
            return response()->json(['success' => false, 'message' => 'Cannot ban self'], 400);
        }

        // 2. [404 檢查] 檢查目標使用者是否存在
        $user = User::find($user_id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // 3. [403 檢查] 無法封鎖另一位管理員
        // 檢查目標使用者的 role 是不是也是 admin
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Cannot ban another admin'], 403);
        }

        // 4. 通過所有檢查，執行封鎖更新
        $user->is_banned = true; // 將封鎖狀態改為 true
        $user->save(); // 儲存回資料庫

        // 5. [200 成功] 回傳符合題目要求的 JSON 格式回應 (注意：範例回應不包含 created_at)
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_banned' => (bool) $user->is_banned, // 強制轉為 boolean 確保輸出 true
                'updated_at' => $user->updated_at->toISOString(), // 時間格式帶 Z
            ]
        ], 200);
    }
}
