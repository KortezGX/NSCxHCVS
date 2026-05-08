<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate; // 引入 Gate facade
use App\Models\User; // 引入 User 模型

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 定義 Gate 來檢查使用者角色
        Gate::define('isAdmin', function (User $user) {
            return $user->role === 'admin'; // 超級管理員
        });

        Gate::define('isManager', function (User $user) {
            return $user->role === 'manager'; // 出版社管理員
        });
    }
}
