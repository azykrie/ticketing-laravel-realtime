<?php

namespace App\Models;

use App\Notifications\TicketReplyNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TicketReply extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'message'];

    protected static function booted(): void
    {
        static::created(function (TicketReply $reply) {
            $ticket = $reply->ticket;
            $senderIsStaff = $reply->user->hasAnyRole(['admin', 'agent']);

            $ids = collect([$ticket->created_by, $ticket->assigned_to]);

            // user membalas tiket yang belum punya assignee: beri tahu semua staff
            if (! $ticket->assigned_to && ! $senderIsStaff) {
                $ids = $ids->merge(User::role(['admin', 'agent'])->pluck('id'));
            }

            $ids = $ids->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->reject(fn($id) => $id === (int) $reply->user_id);

            Notification::send(User::whereIn('id', $ids)->get(), new TicketReplyNotification($reply));
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
