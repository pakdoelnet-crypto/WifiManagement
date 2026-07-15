<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InterfaceTrafficUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $interfaceName;
    public int $rx; // bits per second (download)
    public int $tx; // bits per second (upload)

    /**
     * Create a new event instance.
     */
    public function __construct(string $interfaceName, int $rx, int $tx)
    {
        $this->interfaceName = $interfaceName;
        $this->rx = $rx;
        $this->tx = $tx;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('interface-traffic'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'InterfaceTrafficUpdated';
    }
}
