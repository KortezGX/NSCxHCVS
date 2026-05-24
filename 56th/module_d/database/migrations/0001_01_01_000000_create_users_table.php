<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 只保留題目要求的 user 表，其餘刪除
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();       // 題目登入、註冊與欄位皆使用 username
            $table->string('email')->unique();          // 題目要求的 email 欄位
            $table->string('password');                 // 密碼
            $table->string('role')->default('user');    // 角色：admin, user
            $table->boolean('is_banned')->default(false); // 是否被封鎖
            $table->string('access_token')->nullable(); // 我們自訂用來存 MD5 token 的欄位
            $table->timestamps();                       // 包含預設需要的 created_at 與 updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        // 刪除題目不需要的表
    }
};
