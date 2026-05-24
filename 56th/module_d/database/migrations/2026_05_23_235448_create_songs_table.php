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
        Schema::create('songs', function (Blueprint $table) {
            $table->id(); // 歌曲 ID (主鍵)

            // 所屬專輯 ID (對應 albums 表的 id)
            $table->unsignedBigInteger('album_id');

            $table->string('title'); // 歌曲名稱
            $table->integer('duration_seconds'); // 歌曲長度 (秒數)
            $table->text('lyrics')->nullable(); // 歌詞 (允許為空)

            $table->integer('order')->default(0); // 歌曲在專輯中的播放順序 (數字越小越前面)
            $table->integer('view_count')->default(0); // 點閱數/播放次數 (預設為 0，用於統計 API)

            // 歌曲標籤 (最簡化做法：直接存成逗號分隔字串，例如 "Pop,Rock,Live")
            $table->string('label')->nullable();

            $table->string('cover_path')->nullable(); // 儲存歌曲封面圖片的檔案路徑 (允許為空)

            // 是否被選為專輯封面的一部分 (true/false)
            // 題目要求專輯封面最多可由 3 首歌曲的封面拼成，用這個欄位來篩選
            $table->boolean('is_cover')->default(false);

            $table->softDeletes(); // 軟刪除欄位 (自動產生 deleted_at)
            $table->timestamps(); // 自動產生 created_at 與 updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
