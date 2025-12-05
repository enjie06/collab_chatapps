<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type', 'name', 'avatar'];

    // Jangan eager load apapun
    protected $with = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('role', 'last_read_message_id', 'deleted_at', 'last_cleared_at')
                    ->withTimestamps();
    }

    public function messages()
    {
        // Tanpa global scope dan tanpa cached relation saat dipanggil
        return $this->hasMany(Message::class)->withoutGlobalScopes();
    }

    public function lastReads()
    {
        return $this->hasMany(\App\Models\LastRead::class);
    }

    // MATIKAN pemanggilan relasi messages di method-model
    public function latestMessageFor($userId)
    {
        $pivot = $this->users()->where('user_id', $userId)->first()?->pivot;

        $deletedAt = $pivot?->deleted_at;

        return Message::where('conversation_id', $this->id)
            ->when($deletedAt, fn($q) => $q->where('created_at', '>', $deletedAt))
            ->orderBy('id', 'desc')
            ->first();
    }

    // Hapus relasi messages agar tidak ter-cached
    public function forgetMessagesRelation()
    {
        $this->unsetRelation('messages');
    }
}
