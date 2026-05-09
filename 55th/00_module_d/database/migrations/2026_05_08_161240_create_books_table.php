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
        Schema::create('books', function (Blueprint $table) {
            // 資料表 3 / 3 - 建立 books 資料表
            $table->id();

            // 重點：每本書都要知道是哪家出版社出的 => constrained() 會自動參照 publishers 資料表的 id 欄位 => onDelete('cascade') 表示當出版社被刪除時，相關的書籍也會被自動刪除
            $table->foreignId('publisher_id')->constrained()->onDelete('cascade');

            $table->string('title');  // 書名
            $table->text('description')->nullable();  // 書籍描述，允許為空
            $table->string('author');  // 作者

            // 重點：ISBN 需設為索引 (Index) 以利查詢效能，這是題目規範
            $table->string('isbn')->unique()->index();  // ISBN 編號，unique() 表示 ISBN 必須唯一，index() 表示建立索引以提升查詢效能

            // 重點：新增一個欄位來儲存書籍多張封面圖片的路徑，允許為空
            $table->json('images')->nullable();  // 書籍封面圖片的路徑，使用 JSON 格式儲存多張圖片的路徑，允許為空

            $table->boolean('is_hidden')->default(false); // 是否隱藏

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
