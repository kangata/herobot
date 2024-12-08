<?php

namespace App\Events;

use App\Models\Knowledge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KnowledgeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Knowledge $knowledge
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.' . $this->knowledge->team_id . '.knowledges'),
            new PrivateChannel('team.' . $this->knowledge->team_id . '.knowledges.' . $this->knowledge->id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->knowledge->id,
            'name' => $this->knowledge->name,
            'type' => $this->knowledge->type,
            'status' => $this->knowledge->status,
            'created_at' => $this->knowledge->created_at,
            'updated_at' => $this->knowledge->updated_at,
        ];
    }
} 