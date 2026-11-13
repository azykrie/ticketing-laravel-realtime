<?php

use App\Models\Ticket;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $ticketId = null;
    public string $code = '';
    public string $title = '';

    #[On('delete-ticket')]
    public function confirm(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('delete', $ticket);

        $this->ticketId = $ticket->id;
        $this->code = (string) $ticket->code;
        $this->title = $ticket->title;

        Flux::modal('delete-ticket')->show();
    }

    public function delete(): void
    {
        $ticket = Ticket::findOrFail($this->ticketId);

        $this->authorize('delete', $ticket);

        $ticket->delete();

        $this->resetForm();
        $this->dispatch('ticket-deleted');

        Flux::modal('delete-ticket')->close();
        Flux::toast(variant: 'success', text: 'Ticket deleted successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('ticketId', 'code', 'title');
    }
};
?>

<div>
    <flux:modal name="delete-ticket" class="min-w-[22rem]" wire:close="resetForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete ticket?</flux:heading>
                <flux:text class="mt-2">
                    You are about to delete <strong>{{ $code }}</strong>
                    ({{ $title }}). This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>