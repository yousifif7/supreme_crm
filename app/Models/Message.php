<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    //
    protected $fillable = ['conversation_id', 'sender_id', 'message', 'type', 'attachment', 'deleted'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function readByUserIds()
    {
        return $this->hasMany(MessageRead::class)->pluck('user_id')->toArray();
    }

    public function readers()
{
    return $this->belongsToMany(User::class, 'message_reads')
        ->withPivot('read_at')
        ->withTimestamps();
}

public function readReceipts()
{
    return $this->hasMany(MessageRead::class);
}
}
