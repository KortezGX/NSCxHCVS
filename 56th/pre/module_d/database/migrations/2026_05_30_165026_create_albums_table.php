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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publisher_id')->constrained('users'); // foreignId 表示這是一個外鍵，constrained('users') 表示它參考 users 表的 id 欄位
            $table->string('title'); // 專輯名稱
            $table->string('artist'); // 藝術家名稱
            $table->integer('release_year'); // 發行年份
            $table->string('genre'); // 音樂類型
            $table->text('description')->nullable(); // 專輯描述
            $table->string('cover_image')->nullable(); // 封面圖片欄位
            $table->softDeletes(); // 支援軟刪除

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
