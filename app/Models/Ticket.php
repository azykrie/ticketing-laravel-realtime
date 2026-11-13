<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Events\TicketChanged;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'code',
        'title',
        'description',
        'status',
        'priority',
        'category_id',
        'created_by',
        'assigned_to',
        'closed_at',
    ];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(function (Ticket $ticket) {
            $ticket->updateQuietly([
                'code' => 'TCK-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT),
            ]);

            // beri tahu semua staff, kecuali pembuatnya sendiri
            $staff = User::role(['admin', 'agent'])
                ->where('id', '!=', auth()->id())
                ->get();

            Notification::send($staff, new NewTicketNotification($ticket));

            TicketChanged::dispatch($ticket->id);
        });

        static::updated(function (Ticket $ticket) {
            $actorId = auth()->id();

            // di-assign ke seseorang (bukan oleh dirinya sendiri)
            if ($ticket->wasChanged('assigned_to') && $ticket->assigned_to && (int) $ticket->assigned_to !== (int) $actorId) {
                $ticket->assignee->notify(new TicketUpdatedNotification(
                    $ticket,
                    $ticket->code . ' was assigned to you'
                ));
            }

            // status berubah: beri tahu pembuat tiket
            if ($ticket->wasChanged('status') && (int) $ticket->created_by !== (int) $actorId) {
                $ticket->creator->notify(new TicketUpdatedNotification(
                    $ticket,
                    $ticket->code . ' status is now ' . str_replace('_', ' ', $ticket->status)
                ));
            }

            TicketChanged::dispatch($ticket->id);
        });

        static::deleted(fn(Ticket $ticket) => TicketChanged::dispatch($ticket->id));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'in_progress' => 'amber',
            'resolved' => 'green',
            default => 'zinc',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'low' => 'zinc',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'zinc',
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
