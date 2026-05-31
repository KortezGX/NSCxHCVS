<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能

class Song extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 自動幫你處理前端陣列與資料庫 JSON 的轉換
    protected $casts = [
        'label'    => 'array',   // 存進去自動變 JSON，撈出來自動變 PHP 陣列
        'is_cover' => 'boolean', // 強制轉成布林值
    ];

    // 動態屬性：自動組裝題目要求的 cover_image_url
    protected $appends = ['cover_image_url'];

    public function getCoverImageUrlAttribute()
    {
        return "/api/songs/{$this->id}/cover";
    }
}
