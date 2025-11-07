<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // 🔹 Relasi ke messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // 🔹 Relasi ke conversations (pivot table)
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // public function isOnline()
    // {
    //     return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    // }

    public function getIsOnlineAttribute()
    {
        return cache()->has('user-is-online-' . $this->id);
    }


}
