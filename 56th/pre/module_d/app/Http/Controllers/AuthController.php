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

    // 2. 使用者註冊 (POST /api/register)
    public function register(Request $request)
    {
        // 取得輸入的欄位
        $username = $request->input('username');
        $email = $request->input('email');
        $password = $request->input('password');

        // 認證失敗 : 缺少必要欄位
        if (empty($username) || empty($email) || empty($password)) {
            return response()->json(['success' => false, 'message' => 'Validation failed'], 400);
        }

        // 使用者名稱已被使用
        if (User::where('username', $username)->exists()) {
            return response()->json(['success' => false, 'message' => 'Username already taken'], 409);
        }

        // 郵件已被使用
        if (User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'message' => 'Email already taken'], 409);
        }

        // 一對一存入
        $user = new User();
        $user->username  = $username;
        $user->email     = $email;
        $user->password  = $password; // User model 有 'password' => 'hashed' cast，存入時會自動雜湊
        $user->role      = 'user'; // 預設都是一般使用者
        $user->is_banned = false;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'         => $user->id,
                    'username'   => $user->username,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    // 轉換為題目要求的 2025-10-23T15:00:00.000Z 格式
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ]
            ]
        ], 201);
    }

    // 9. 使用者登出 (POST /api/logout)
    public function logout(Request $request)
    {
        // 錯誤判定都在 CheckToken 的 middleware 做完了，這裡只需要清除資料庫 token 就好

        // 從自訂的 Middleware 拿取抓到的當前使用者（CheckToken 已保證一定存在，不用再判斷 null）
        $user = $request->input('current_user');

        $user->access_token = null; // 清空 Token
        $user->save();

        return response()->json(['success' => true]);
    }
}
