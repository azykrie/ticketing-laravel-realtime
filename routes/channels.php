<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tickets.{ticketId}', function ($user, int $ticketId) {
    $ticket = Ticket::find($ticketId);

    return $ticket && $user->can('view', $ticket);
});

Broadcast::channel('tickets', function ($user) {
    return $user->hasAnyRole(['admin', 'agent']);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});