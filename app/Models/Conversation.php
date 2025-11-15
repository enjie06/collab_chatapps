<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type', 'name', 'avatar'];

    // 🔹 Relasi ke semua user yang ikut percakapan
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('role', 'last_read_message_id', 'deleted_at')
                    ->withTimestamps();
    }

    // 🔹 Relasi ke semua pesan di percakapan
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    public function lastReads()
    {
        return $this->hasMany(\App\Models\LastRead::class);
    }

    public function latestMessageFor($userId)
    {
        $pivot = $this->users()->where('user_id', $userId)->first()?->pivot;

        $deletedAt = $pivot?->deleted_at;

        return $this->messages()
            ->when($deletedAt, fn($q) => $q->where('created_at', '>', $deletedAt))
            ->orderBy('id', 'desc')
            ->first();
    }
}
