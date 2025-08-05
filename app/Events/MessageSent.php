<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    // Constructor
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // Broadcasting the message to a private channel
   public function broadcastOn()
{
    if ($this->message->conversation->type == 'one-to-one') {
        // For one-to-one chats, use a private channel
        return new PrivateChannel('chat.' . $this->message->conversation->users->pluck('id')->sort()->join('-'));
    } else {
        // For group chats, use a public channel
        return new Channel('chat.' . $this->message->conversation->id);
    }
}


    // Optionally you can set a custom broadcast name
    public function broadcastAs()
    {
        return 'message.sent';
    }
}