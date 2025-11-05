<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    // 🔹 Relasi ke semua user yang ikut percakapan
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // 🔹 Relasi ke semua pesan di percakapan
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
