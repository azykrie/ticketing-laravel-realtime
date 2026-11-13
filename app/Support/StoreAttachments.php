<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class StoreAttachments
{
    public static function handle(Model $model, array $files, int $userId): void
    {
        foreach ($files as $file) {
            $path = $file->store('attachments/' . now()->format('Y/m'), 'local');

            $model->attachments()->create([
                'user_id' => $userId,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}