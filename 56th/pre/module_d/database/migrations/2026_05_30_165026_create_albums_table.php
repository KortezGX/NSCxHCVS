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
        // 對齊 module_c_db.sql 的 albums 表命名（deleted_at 是 SQL 沒有的，但題目規格第 18 題要求軟刪除，所以保留）
        Schema::create('albums', function (Blueprint $table) {
            $table->id('album_id');

            $table->foreignId('publisher_id')->constrained('users', 'user_id'); // 參考 users 表的 user_id 欄位
            $table->string('title'); // 專輯名稱
            $table->string('artist'); // 藝術家名稱
            $table->integer('release_year'); // 發行年份
            $table->string('genre'); // 音樂類型
            $table->text('description')->nullable(); // 專輯描述
            $table->softDeletes(); // 支援軟刪除（題目規格要求，SQL 參考檔沒有這欄）

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
