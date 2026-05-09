<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    // 定義與 出版社的關聯 (多對一)
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    // 定義好圖片的格式 方便在 Controller 中直接使用 $book->images 來取得圖片路徑陣列
    protected $casts = [
        'images' => 'array', // 將 images 欄位轉換為陣列格式
    ];
}
