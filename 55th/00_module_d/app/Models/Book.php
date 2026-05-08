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
}
