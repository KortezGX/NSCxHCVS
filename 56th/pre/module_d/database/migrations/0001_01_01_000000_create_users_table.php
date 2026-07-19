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
        // 對齊 module_c_db.sql 的 users 表命名
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');

            $table->string('username')->unique();       // 題目登入、註冊與欄位皆使用 username
            $table->string('email')->unique();          // 題目要求的 email 欄位
            $table->string('password_hash');            // 密碼（雜湊後）
            $table->enum('role', ['admin', 'publisher', 'user'])->default('user'); // 角色
            $table->boolean('is_banned')->default(false); // 是否被封鎖
            $table->string('token')->nullable();         // 存 MD5 token 的欄位

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
