<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 引入 BelongsTo 類別以便定義關聯
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 對齊 module_c_db.sql：主鍵欄位是 album_id，不是 Laravel 預設的 id
    protected $primaryKey = 'album_id';

    // 定義關聯：這張專輯屬於哪一個發布的管理員（User 的主鍵是 user_id，要明講）
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id', 'user_id');
    }

    // 定義關聯：這張專輯底下有哪些歌曲（第 11 題統計要用 withSum 加總 view_count）
    public function songs(): HasMany
    {
        return $this->hasMany(Song::class, 'album_id', 'album_id');
    }
}
