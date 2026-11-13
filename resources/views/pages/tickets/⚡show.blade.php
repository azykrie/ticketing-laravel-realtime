<?php

use App\Events\TicketReplied;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Support\StoreAttachments;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Ticket Detail')] class extends Component {
    use WithFileUploads;

    public Ticket $ticket;
    public string $message = '';
    public array $files = [];

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket;
        $this->ticket->load('attachments');
    }

    public function getListeners(): array
    {
        return [
            "echo-private:tickets.{$this->ticket->id},TicketReplied" => 'refreshTicket',
            "echo-private:tickets.{$this->ticket->id},.TicketReplied" => 'refreshTicket',
            "echo-private:tickets.{$this->ticket->id},TicketChanged" => 'refreshTicket',
            "echo-private:tickets.{$this->ticket->id},.TicketChanged" => 'refreshTicket',
        ];
    }

    public function refreshTicket(): void
    {
        $this->ticket->refresh()->load('attachments');
    }

    public function reply(): void
    {
        $this->authorize('reply', $this->ticket);

        $data = $this->validate([
            'message' => 'required_without:files|nullable|string|max:5000',
            'files' => 'array|max:5',
            'files.*' => 'file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $reply = $this->ticket->replies()->create([
            'user_id' => auth()->id(),
            'message' => $data['message'] ?? '',
        ]);

        StoreAttachments::handle($reply, $this->files, auth()->id());

        // agent/admin yang pertama membalas: otomatis In Progress
        if (auth()->user()->hasAnyRole(['admin', 'agent']) && $this->ticket->status === 'open') {
            $this->ticket->update([
                'status' => 'in_progress',
                'assigned_to' => $this->ticket->assigned_to ?? auth()->id(),
            ]);
        }

        broadcast(new TicketReplied($reply))->toOthers();

        $this->reset('message', 'files');
        $this->ticket->refresh()->load('attachments');
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = Attachment::findOrFail($id);

        // pastikan lampiran memang milik tiket ini
        abort_unless($attachment->ticket()?->id === $this->ticket->id, 404);
        $this->authorize('deleteAttachment', [$this->ticket, $attachment]);

        $attachment->delete(); // file fisik ikut terhapus (hook di model Attachment)

        $this->ticket->refresh()->load('attachments');
        Flux::toast(variant: 'success', text: 'Attachment deleted.');
    }

    public function close(): void
    {
        $this->authorize('close', $this->ticket);

        $this->ticket->update(['status' => 'closed', 'closed_at' => now()]);
        $this->ticket->refresh()->load('attachments');

        Flux::toast(variant: 'success', text: 'Ticket closed.');
    }

    public function reopen(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'agent']), 403);

        $this->ticket->update(['status' => 'open', 'closed_at' => null]);
        $this->ticket->refresh()->load('attachments');

        Flux::toast(variant: 'success', text: 'Ticket reopened.');
    }

    public function with(): array
    {
        return [
            'replies' => $this->ticket->replies()->with(['user.roles', 'attachments'])->oldest()->get(),
        ];
    }
};
?>

<div class="max-w-4xl">
    <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('tickets.index')" wire:navigate>
        Back to tickets
    </flux:button>

    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:text size="sm">{{ $ticket->code }}</flux:text>
            <flux:heading size="xl" level="1">{{ $ticket->title }}</flux:heading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:badge :color="$ticket->priorityColor()">{{ ucfirst($ticket->priority) }}</flux:badge>
            <flux:badge :color="$ticket->statusColor()">{{ str($ticket->status)->replace('_', ' ')->title() }}
            </flux:badge>

            @if ($ticket->status !== 'closed')
                @can('close', $ticket)
                    <flux:button size="sm" variant="danger" icon="lock-closed" wire:click="close"
                        wire:confirm="Close this ticket? You will not be able to reply after it is closed.">
                        Close ticket
                    </flux:button>
                @endcan
            @elseif (auth()->user()->hasAnyRole(['admin', 'agent']))
                <flux:button size="sm" icon="arrow-path" wire:click="reopen">Reopen</flux:button>
            @endif
        </div>
    </div>

    <flux:separator variant="subtle" class="my-6" />

    <div class="mb-6 grid gap-4 text-sm sm:grid-cols-4">
        <div>
            <flux:text size="sm">Category</flux:text>
            <div class="font-medium">{{ $ticket->category?->name }}</div>
        </div>
        <div>
            <flux:text size="sm">Requester</flux:text>
            <div class="font-medium">{{ $ticket->creator?->name }}</div>
        </div>
        <div>
            <flux:text size="sm">Assignee</flux:text>
            <div class="font-medium">{{ $ticket->assignee?->name ?? '-' }}</div>
        </div>
        <div>
            <flux:text size="sm">Created</flux:text>
            <div class="font-medium">{{ $ticket->created_at->format('d M Y H:i') }}</div>
        </div>
    </div>

    <flux:card class="mb-6">
        <flux:heading size="sm">Description</flux:heading>
        <flux:text class="mt-2 whitespace-pre-line">{{ $ticket->description }}</flux:text>
        <x-attachment-list :attachments="$ticket->attachments" :ticket="$ticket" />
    </flux:card>

    <flux:heading size="lg" class="mb-3">Replies ({{ $replies->count() }})</flux:heading>

    <div class="space-y-3">
        @forelse ($replies as $reply)
            <flux:card wire:key="reply-{{ $reply->id }}"
                class="{{ $reply->user_id === auth()->id() ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <flux:avatar size="xs" :name="$reply->user->name" />
                        <span class="font-medium">{{ $reply->user->name }}</span>
                        @if ($reply->user->hasAnyRole(['admin', 'agent']))
                            <flux:badge size="sm" color="blue">Staff</flux:badge>
                        @endif
                    </div>
                    <flux:text size="sm">{{ $reply->created_at->diffForHumans() }}</flux:text>
                </div>

                @if ($reply->message)
                    <flux:text class="mt-3 whitespace-pre-line">{{ $reply->message }}</flux:text>
                @endif

                <x-attachment-list :attachments="$reply->attachments" :ticket="$ticket" />
            </flux:card>
        @empty
            <flux:text>No replies yet.</flux:text>
        @endforelse
    </div>

    <div class="mt-6">
        @if ($ticket->status === 'closed')
            <flux:callout icon="lock-closed" heading="This ticket is closed and cannot be replied to." />
        @else
            <form wire:submit="reply" class="space-y-3">
                <flux:textarea wire:model="message" label="Your reply" rows="4"
                    placeholder="Write your reply..." />

                <div>
                    <flux:input type="file" wire:model="files" label="Attachments" multiple
                        description:trailing="Images or documents, max 5 files, 5 MB each." />

                    <div wire:loading wire:target="files" class="mt-1 text-sm text-zinc-500">Uploading...</div>

                    @error('files') <flux:text class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror
                    @error('files.*') <flux:text class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror
                    @error('message') <flux:text class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror

                    @if ($files)
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($files as $i => $file)
                                <li wire:key="file-{{ $i }}"
                                    class="flex items-center justify-between rounded border border-zinc-200 px-2 py-1 dark:border-zinc-700">
                                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                    <flux:button size="xs" variant="ghost" icon="x-mark"
                                        wire:click="removeFile({{ $i }})" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="paper-airplane">Send Reply</flux:button>
                </div>
            </form>
        @endif
    </div>
</div>