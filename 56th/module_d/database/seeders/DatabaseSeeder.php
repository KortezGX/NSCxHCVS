<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // 引入 Hash facade 以便使用 Hash::make() 來加密密碼

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 刪除原本用不上的程式碼
        // 這是寫在檔案裡的預設資料陣列
        $users = [
            ['username' => 'admin', 'email' => 'admin@example.com', 'password' => 'admin123', 'role' => 'admin', 'is_banned' => false],
            ['username' => 'user1', 'email' => 'user1@example.com', 'password' => 'user123', 'role' => 'user', 'is_banned' => false],
            ['username' => 'user2', 'email' => 'user2@example.com', 'password' => 'user223', 'role' => 'user', 'is_banned' => true], // 預設封鎖，等等可以測試 403
        ];

        // 透過迴圈，一筆一筆 new 出來並 save() 存進資料庫
        foreach ($users as $u) {
            $user = new User();
            $user->username  = $u['username'];
            $user->email     = $u['email'];
            $user->password  = Hash::make($u['password']);
            $user->role      = $u['role'];
            $user->is_banned = $u['is_banned'];
            $user->save();
        }
    }
}
