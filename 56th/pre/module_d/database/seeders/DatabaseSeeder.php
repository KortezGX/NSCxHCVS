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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // 注解掉上面用不上的程式碼

        // 這是寫在題目裡的預設資料陣列
        $users = [
            ['username' => 'admin', 'email' => 'admin@web.wsa', 'password' => 'adminpass', 'role' => 'admin', 'is_banned' => false],
            ['username' => 'user1', 'email' => 'user1@web.wsa', 'password' => 'user1pass', 'role' => 'user', 'is_banned' => false],
            ['username' => 'user2', 'email' => 'user2@web.wsa', 'password' => 'user1pass', 'role' => 'user', 'is_banned' => true], // 預設封鎖，等等可以測試 403
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
