<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'reply_to_id'
    ];

    // RELASI UNTUK REPLY
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    // 🔹 Relasi ke conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // 🔹 Relasi ke pengirim (user)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Relasi ke lampiran (gambar/audio)
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function attachment()
    {
        return $this->hasOne(\App\Models\Attachment::class);
    }
}
