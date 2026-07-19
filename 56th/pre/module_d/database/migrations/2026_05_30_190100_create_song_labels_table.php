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
        // 對齊 module_c_db.sql 的 song_labels，歌曲與曲風的多對多關聯表
        Schema::create('song_labels', function (Blueprint $table) {
            $table->id('song_label_id');
            $table->foreignId('song_id')->constrained('songs', 'song_id')->onDelete('cascade');
            $table->foreignId('label_id')->constrained('labels', 'label_id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_labels');
    }
};
