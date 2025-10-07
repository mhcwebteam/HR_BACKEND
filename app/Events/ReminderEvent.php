<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReminderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('reminder-channel');
    }

    /**
     * The event's broadcast name (simplifies frontend binding)
     */
    public function broadcastAs()
    {
        return 'reminder-notification'; // 👈 add this here
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message
        ];
    }
}
