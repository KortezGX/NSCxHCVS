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
            $table->id();

            // 關聯到專輯表，如果專輯被刪除，底下的歌曲也一併連帶刪除 (Cascade)
            $table->foreignId('album_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->integer('duration_seconds');
            $table->text('lyrics')->nullable();
            $table->integer('order');
            $table->integer('view_count')->default(0); // 題目要求預設為 0

            // 用 json 欄位直接存英文曲風標籤陣列，例如 ["Rock", "Pop"]
            $table->json('label')->nullable();
            $table->boolean('is_cover')->default(false);

            // 存圖片在 storage 的實體路徑
            $table->string('cover_image_path')->nullable();

            $table->softDeletes(); // 支援軟刪除

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
