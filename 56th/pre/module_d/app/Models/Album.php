<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 先在最外層引用
use Illuminate\Database\Eloquent\SoftDeletes; // 引入軟刪除功能
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 引入 BelongsTo 類別以便定義關聯

class Album extends Model
{
    use SoftDeletes; // 啟用軟刪除功能

    // 定義關聯：這張專輯屬於哪一個發布的管理員
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }
}
