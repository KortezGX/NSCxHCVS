<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 引入 Auth facade 來使用 Laravel 的認證功能
use Illuminate\Support\Facades\Hash; // 引入 Hash facade 來使用 Laravel 的密碼加密功能
use App\Models\User; // 引入寫好的 User 模型

class AuthController extends Controller
{
    // 顯示登入頁面
    public function show()
    {
        return view('login'); // 確保你有 resources/views/login.blade.php
    }

    // 處理登入動作
    public function login(Request $request)
    {
        $account = $request->input('account');
        $password = $request->input('password');

        // A. 判斷固定 Admin (不用密碼 hash，直接寫死字串比對)
        if ($account === 'admin' && $password === '1234') {
            $user = User::firstOrCreate(
                ['account' => 'admin'], // 先去資料庫找 account 是 admin 的使用者
                [ // 如果找不到就建立一個新的 account 是 admin 的使用者，密碼直接用 Hash::make 加密，role 是 admin
                    'name' => '超級管理員',
                    'password' => Hash::make('1234'),
                    'role' => 'admin'
                ]
            );
            Auth::login($user);
            return redirect()->route('login.show')->with('msg', '超級管理員登入成功！');
        }

        // B. 一般出版社管理員 (走資料庫正規驗證)
        if (Auth::attempt(['account' => $account, 'password' => $password])) {
            // attempt 會自動幫你檢查 account 和 password 是否正確，並且會自動把使用者資料載入 Auth::user()
            $request->session()->regenerate();  // 登入成功後重新生成 session ID 並儲存使用者資料到 session 中

            return redirect()->route('login.show')->with('msg', '出版社管理員登入成功！');
        }

        // C. 失敗：回上一頁並帶錯誤訊息
        return back()->with(['msg' => '帳號或密碼錯誤']);
    }

    // 登出
    public function logout(Request $request)
    {
        Auth::logout();  // 使用 Laravel 的 Auth facade 來登出使用者
        $request->session()->invalidate(); // 清空所有 session
        $request->session()->regenerateToken(); // 重新生成 CSRF token
        return redirect()->route('login.show');
    }
}
