<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    use HasFactory;

    // 定義與 出版社聯絡人的關聯 (一對多)
    public function contacts()
    {
        return $this->hasMany(PublisherContact::class);
    }

    // 定義與 出版社管理員的關聯 (一對多)
    public function managers()
    {
        return $this->hasMany(User::class);
    }

    // 定義與 書籍的關聯 (一對多)
    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
