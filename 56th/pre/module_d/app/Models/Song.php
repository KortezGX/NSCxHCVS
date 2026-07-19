<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Song extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 對齊 module_c_db.sql：主鍵欄位是 song_id，不是 Laravel 預設的 id
    protected $primaryKey = 'song_id';

    protected $casts = [
        'is_cover' => 'boolean', // 強制轉成布林值
    ];

    // 動態屬性：自動組裝題目要求的 cover_image_url，以及把關聯表組回題目要求的 label 陣列格式
    protected $appends = ['cover_image_url', 'label'];

    public function getCoverImageUrlAttribute()
    {
        return "/api/songs/{$this->song_id}/cover";
    }

    // 曲風標籤改用 labels + song_labels 關聯表（對齊 SQL），不再用 JSON 欄位存
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'song_labels', 'song_id', 'label_id', 'song_id', 'label_id');
    }

    // 題目要求的回傳格式是純字串陣列，例如 ["Rock", "Pop"]，所以把關聯撈出來的 Label 轉成純名稱陣列
    public function getLabelAttribute()
    {
        return $this->labels->pluck('name')->values()->all();
    }
}
