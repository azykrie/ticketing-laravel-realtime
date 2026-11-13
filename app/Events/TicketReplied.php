<?php

namespace App\Events;

use App\Models\TicketReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TicketReply $reply) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tickets.' . $this->reply->ticket_id)];
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->reply->id];
    }
}