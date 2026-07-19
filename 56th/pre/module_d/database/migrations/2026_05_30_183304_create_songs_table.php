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
        // 對齊 module_c_db.sql 的 songs 表命名；曲風標籤改用 labels + song_labels 關聯表，不再用 JSON 欄位
        Schema::create('songs', function (Blueprint $table) {
            $table->id('song_id');

            // 關聯到專輯表，如果專輯被刪除，底下的歌曲也一併連帶刪除 (Cascade)
            $table->foreignId('album_id')->constrained('albums', 'album_id')->onDelete('cascade');
            $table->string('title');
            $table->integer('duration_seconds');
            $table->text('lyrics')->nullable();
            $table->integer('track_order'); // SQL 用 track_order，避開 order 這個 MySQL 保留字
            $table->integer('view_count')->default(0); // 題目要求預設為 0，SQL 沒有這欄但統計 API 需要，保留

            $table->boolean('is_cover')->default(false);

            // 存圖片在 storage 的實體路徑
            $table->string('cover_image_path')->nullable();

            $table->softDeletes(); // 支援軟刪除（SQL 有這欄，一致）

            $table->timestamps();
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
