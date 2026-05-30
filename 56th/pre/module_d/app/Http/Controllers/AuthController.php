<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// 先在最外層引用
use App\Models\User;
use Illuminate\Support\Facades\Hash; // 引入 Hash facade 以便使用 Hash::make

class AuthController extends Controller
{
    // 1. 使用者登入 (POST /api/login)
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // 尋找使用者
        $user = User::where('username', $username)->first();

        // 登入時提供了錯誤的帳號或密碼。
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Login failed'], 400);
        }

        // 被封鎖的使用者試圖登入或存取受保護功能。
        if ($user->is_banned) {
            return response()->json(['success' => false, 'message' => 'User is banned'], 403);
        }

        // 題目核心要求：Token 由帳號進行 MD5 雜湊後轉全小寫十六進位
        $token = strtolower(md5($username));

        // 將 Token 存入資料庫
        $user->access_token = $token;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token, // 欄位名改為 token
                'user' => [
                    'id'         => $user->id,
                    'username'   => $user->username,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    // 回傳符合 ISO 8601 / JSON 規格的時間格式
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ]
            ]
        ]);
    }
}
