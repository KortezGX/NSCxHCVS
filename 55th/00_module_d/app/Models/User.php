<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account', // 帳號
        'password', // 密碼
        'name', // 姓名
        'role', // 身分 超級管理員 / 出版社管理員
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime', // 如果不使用 email 驗證，可以移除這行
        'password' => 'hashed', // 題目要求密碼加密 因此使用 Laravel 10 的 hashed 屬性
    ];

    // 定義與 出版社的關聯 (多對一)
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
}
