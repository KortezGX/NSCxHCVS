<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // 引入 User 模型

class AdminController extends Controller
{
    // 列出所有使用者 (GET /api/users)
    public function index()
    {
        // 撈出所有使用者，用符合題目的欄位格式輸出
        $users = User::all();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // 更改使用者角色 (PUT /api/users/{id}/role)
    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->role = $request->input('role'); // admin 或 user
        $user->save();

        return response()->json(['success' => true]);
    }

    // 停權/解除停權使用者 (PUT /api/users/{id}/ban)
    public function toggleBan(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // 依照前端傳來的 is_banned (true/false) 變更
        $user->is_banned = $request->input('is_banned');

        // 如果被停權，順便把他的 Token 拔掉讓他斷線
        if ($user->is_banned) {
            $user->access_token = null;
        }

        $user->save();

        return response()->json(['success' => true]);
    }
}
