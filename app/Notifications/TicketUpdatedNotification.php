<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketUpdatedNotification extends Notification
{
    public function __construct(public Ticket $ticket, public string $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'code' => $this->ticket->code,
            'message' => $this->message,
        ];
    }
}