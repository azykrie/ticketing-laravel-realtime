<?php

namespace App\Notifications;

use App\Models\TicketReply;
use Illuminate\Notifications\Notification;

class TicketReplyNotification extends Notification
{
    public function __construct(public TicketReply $reply) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->reply->ticket_id,
            'code' => $this->reply->ticket->code,
            'message' => $this->reply->user->name . ' replied to ' . $this->reply->ticket->code,
        ];
    }
}