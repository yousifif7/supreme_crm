<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    //
    protected $fillable = ['conversation_id', 'sender_id', 'content', 'type', 'media_url', 'deleted'];

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
}
