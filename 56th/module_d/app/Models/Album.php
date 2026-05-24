<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. 引入軟刪除

class Album extends Model
{
    use SoftDeletes; // 2. 啟用軟刪除功能
}
