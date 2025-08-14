<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    //
    protected $fillable = [
        'type', 'name','icon_path'
    ];
  public function participants()
{
    return $this->belongsToMany(User::class, 'conversation_user')
        ->withTimestamps();
}

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

        public function pinnedByUsers()
{
    return $this->hasMany(UserPinnedConversation::class, 'conversation_id');
}
}
