<?php

namespace App\Events;

use App\Models\Channel;
use Illuminate\Broadcasting\Channel as BroadcastingChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Channel $channel,
        public string $status,
    ) {}

    public function broadcastOn(): BroadcastingChannel
    {
        return new PrivateChannel('channel.'.$this->channel->id);
    }
}
