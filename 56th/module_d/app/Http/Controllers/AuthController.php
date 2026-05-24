<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // 引入 User 模型
use Illuminate\Support\Facades\Hash; // 引入 Hash facade 以便使用 Hash::make

class AuthController extends Controller
{
    // 1. 使用者註冊 (POST /api/register)
    public function register(Request $request)
    {
        // 現場比賽若時間緊迫，最簡化驗證：只要防重複帳號就好
        $exists = User::where('username', $request->input('username'))->first();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Username already exists'], 400);
        }

        // 一對一存入
        $user = new User();
        $user->username  = $request->input('username');
        $user->email     = $request->input('email');
        $user->password  = Hash::make($request->input('password'));
        $user->role      = 'user'; // 預設都是一般使用者
        $user->is_banned = false;
        $user->save();

        return response()->json(['success' => true]);
    }

    // 2. 使用者登入 (POST /api/login)
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // 尋找使用者
        $user = User::where('username', $username)->first();

        // 驗證密碼
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid username or password'], 401);
        }

        // 檢查是否被停權
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
            'access_token' => $token,
            'role' => $user->role
        ]);
    }

    // 3. 使用者登出 (POST /api/logout)
    public function logout(Request $request)
    {
        // 從我們自訂的 Middleware 拿取抓到的當前使用者
        $user = $request->get('current_user');

        if ($user) {
            $user->access_token = null; // 清空 Token
            $user->save();
        }

        return response()->json(['success' => true]);
    }
}
