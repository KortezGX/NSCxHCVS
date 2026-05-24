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
            $table->id(); // 專輯 ID (主鍵)

            $table->string('title'); // 專輯名稱
            $table->string('artist'); // 藝人/歌手名稱
            $table->integer('release_year'); // 發行年份 (例如: 2026)
            $table->string('genre'); // 音樂流派/風格 (例如: Pop, Rock)
            $table->text('description')->nullable(); // 專輯描述 (允許為空)

            // 建立這個專輯的管理員 ID (對應 users 表的 id)
            $table->unsignedBigInteger('publisher_id');

            $table->softDeletes(); // 軟刪除欄位 (會自動產生 deleted_at，用於題目要求的刪除功能)
            $table->timestamps(); // 自動產生建立時間 (created_at) 與更新時間 (updated_at)
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
