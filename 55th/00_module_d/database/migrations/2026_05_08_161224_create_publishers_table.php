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
        Schema::create('publishers', function (Blueprint $table) {
            // 資料表 1 / 3 - 建立 publishers 資料表
            $table->id();

            $table->string('name');         // 出版社名稱
            $table->string('address');      // 地址
            $table->string('phone');        // 電話
            $table->string('isbn_code')->unique(); // 出版社代碼 (例如題目要求的 01 或 986)
            $table->boolean('is_active')->default(true); // 啟用狀態

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};
