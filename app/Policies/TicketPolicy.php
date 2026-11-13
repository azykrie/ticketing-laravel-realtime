<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    protected function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'agent']);
    }

    public function viewAny(User $user): bool
    {
        return true; // daftar difilter di query
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->isStaff($user) || $ticket->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    // staff: semua. user: hanya tiket sendiri.
    public function update(User $user, Ticket $ticket): bool
    {
        return $this->isStaff($user) || $ticket->created_by === $user->id;
    }

    // hanya staff yang boleh ubah status/prioritas/kategori/assignee
    public function manage(User $user, Ticket $ticket): bool
    {
        return $this->isStaff($user);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status !== 'closed';
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $this->isStaff($user) || $ticket->created_by === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAttachment(User $user, Ticket $ticket, \App\Models\Attachment $attachment): bool
{
    if ($user->hasRole('admin')) {
        return true;
    }

    return $attachment->user_id === $user->id
        && $ticket->status !== 'closed'
        && $this->view($user, $ticket);
}
}
