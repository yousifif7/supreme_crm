<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'admin_id',
        'type',
        'name',
        'icon_path'
    ];
    public function participants()
    {
        return $this->belongsToMany(
            User::class,        // Related model
            'conversation_user', // Pivot table
            'conversation_id',   // Foreign key on pivot table for this model (Conversation)
            'user_id'            // Foreign key on pivot table for related model (User)
        )
            ->withTimestamps();      // Manage created_at/updated_at on pivot
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
