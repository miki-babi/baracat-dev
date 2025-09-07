<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lunar\Base\LunarUser as LunarUserContract;
use Lunar\Base\Traits\LunarUser as LunarUserTrait;

class User extends Authenticatable implements LunarUserContract
{
    use HasApiTokens, HasFactory, Notifiable, LunarUserTrait;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
