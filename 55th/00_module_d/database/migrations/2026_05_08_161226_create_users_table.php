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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('account')->unique(); // 帳號
            $table->string('password'); // 密碼
            $table->string('name'); // 姓名
            $table->string('role'); // 身分

            // 重點：管理員都要知道是哪家出版社的 => constrained() 會自動參照 publishers 資料表的 id 欄位 => onDelete('cascade') 表示當出版社被刪除時，相關的出版社管理員也會被自動刪除
            // 注意：這裡的 publisher_id 允許為 null，因為超級管理員不一定要關聯到出版社
            $table->foreignId('publisher_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
