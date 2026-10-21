<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $channel;
    public string $eventName;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(string $channel, string $eventName, array $data)
    {
        $this->channel = $channel;
        $this->eventName = $eventName;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->channel)
        ];
    }

    /**
     * Get the name of the event broadcast.
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    /**
     * Get the data that should be broadcast.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
