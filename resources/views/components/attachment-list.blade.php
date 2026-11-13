@props(['attachments', 'ticket'])

@if ($attachments->isNotEmpty())
    <div class="mt-3 flex flex-wrap gap-3">
        @foreach ($attachments as $a)
            <div wire:key="att-{{ $a->id }}" class="relative">
                @if ($a->isImage())
                    <a href="{{ route('attachments.show', $a) }}" target="_blank"
                        class="block overflow-hidden rounded border border-zinc-200 dark:border-zinc-700">
                        <img src="{{ route('attachments.show', $a) }}" alt="{{ $a->original_name }}"
                            class="h-24 w-24 object-cover" loading="lazy">
                    </a>
                @else
                    <a href="{{ route('attachments.show', $a) }}"
                        class="flex items-center gap-2 rounded border border-zinc-200 px-3 py-2 pe-8 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <flux:icon.document-text class="size-5" />
                        <span class="max-w-40 truncate">{{ $a->original_name }}</span>
                        <span class="text-xs text-zinc-500">{{ $a->humanSize() }}</span>
                    </a>
                @endif

                @can('deleteAttachment', [$ticket, $a])
                    <button type="button" wire:click="deleteAttachment({{ $a->id }})"
                        wire:confirm="Delete this attachment?"
                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                        title="Delete">&times;</button>
                @endcan
            </div>
        @endforeach
    </div>
@endif