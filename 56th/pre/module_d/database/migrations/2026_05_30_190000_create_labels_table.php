<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 對齊 module_c_db.sql 的 labels 表，8 個預設曲風固定用英文（題目統計 API 範例也是用英文篩選 labels=Pop,Rock）
        Schema::create('labels', function (Blueprint $table) {
            $table->id('label_id');
            $table->string('name');
        });

        DB::table('labels')->insert([
            ['label_id' => 1, 'name' => 'Pop'],
            ['label_id' => 2, 'name' => 'Rock'],
            ['label_id' => 3, 'name' => 'Hip-Hop'],
            ['label_id' => 4, 'name' => 'Electronic'],
            ['label_id' => 5, 'name' => 'Jazz'],
            ['label_id' => 6, 'name' => 'Classical'],
            ['label_id' => 7, 'name' => 'Chill'],
            ['label_id' => 8, 'name' => 'Country'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
