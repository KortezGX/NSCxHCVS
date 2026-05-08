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
        Schema::create('publisher_contacts', function (Blueprint $table) {
            // 資料表 2 / 3 - 建立 publishers contacts 資料表
            $table->id();

            // 重點：每個聯絡人都要知道是哪家出版社的 => constrained() 會自動參照 publishers 資料表的 id 欄位 => onDelete('cascade') 表示當出版社被刪除時，相關的書籍也會被自動刪除
            $table->foreignId('publisher_id')->constrained()->onDelete('cascade');

            $table->string('name');   // 姓名
            $table->string('phone');  // 電話
            $table->string('email');  // Email

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publisher_contacts');
    }
};
