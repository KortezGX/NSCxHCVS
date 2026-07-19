<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    // 對齊 module_c_db.sql：主鍵欄位是 label_id，不是 Laravel 預設的 id
    protected $primaryKey = 'label_id';

    // 這張表沒有 created_at / updated_at
    public $timestamps = false;

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'song_labels', 'label_id', 'song_id', 'label_id', 'song_id');
    }
}
