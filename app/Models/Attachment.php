<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'disk', 'path', 'original_name', 'mime', 'size'];

    protected static function booted(): void
    {
        // hapus file fisik saat record dihapus
        static::deleted(fn (Attachment $a) => Storage::disk($a->disk)->delete($a->path));
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1) . ' MB'
            : max(1, round($this->size / 1024)) . ' KB';
    }

    // tiket induk, baik lampiran langsung maupun lewat balasan
    public function ticket(): ?Ticket
    {
        return $this->attachable instanceof TicketReply
            ? $this->attachable->ticket
            : $this->attachable;
    }
}