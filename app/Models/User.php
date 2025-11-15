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
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    // Pesan yang dikirim user ini
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Percakapan yang user ikut
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
                    ->withPivot('role', 'last_read_message_id', 'deleted_at')
                    ->withTimestamps();
    }

    // Status online
    // public function isOnline()
    // {
    //     return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    // }

    public function getIsOnlineAttribute()
    {
        return cache()->has('user-is-online-' . $this->id);
    }

    // Request yang dia kirim
    public function sentRequests()
    {
        return $this->hasMany(Friendship::class, 'requester_id')
                    ->where('status', 'pending');
    }

    // Request yang dia terima
    public function receivedRequests()
    {
        return $this->hasMany(Friendship::class, 'receiver_id')
                    ->where('status', 'pending');
    }

    // Permintaan dikirim (pending)
    public function sentRequestsPending()
    {
        return $this->hasMany(Friendship::class, 'requester_id')
            ->where('status', 'pending')
            ->with('receiver');
    }

    // Permintaan diterima (pending)
    public function receivedRequestsPending()
    {
        return $this->hasMany(Friendship::class, 'receiver_id')
            ->where('status', 'pending')
            ->with('requester');
    }

    public function friends()
    {
        $friendsAsRequester = $this->belongsToMany(User::class, 'friendships', 'requester_id', 'receiver_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->get();

        $friendsAsReceiver = $this->belongsToMany(User::class, 'friendships', 'receiver_id', 'requester_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->get();

        return $friendsAsRequester->merge($friendsAsReceiver)->unique('id')->values();
    }

    public function lastReads()
    {
        return $this->hasMany(\App\Models\LastRead::class);
    }
}
