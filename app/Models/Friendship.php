<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    protected $fillable = ['requester_id', 'receiver_id', 'status', 'is_blocked', 'blocked_by'];
    protected $casts = ['is_blocked' => 'boolean'];

    // relasi buat dipakai di blade (req->requester / req->receiver)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // helper: relasi dua arah
    public static function between($a, $b)
    {
        return static::where(function ($q) use ($a, $b) {
            $q->where('requester_id', $a)->where('receiver_id', $b);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('requester_id', $b)->where('receiver_id', $a);
        });
    }
}
