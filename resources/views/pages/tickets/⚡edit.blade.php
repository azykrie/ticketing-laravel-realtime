<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $ticketId = null;
    public string $title = '';
    public string $description = '';
    public string $category_id = '';
    public string $priority = 'medium';
    public string $status = 'open';
    public string $assigned_to = '';

    #[Computed]
    public function isStaff(): bool
    {
        return auth()
            ->user()
            ->hasAnyRole(['admin', 'agent']);
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function agents()
    {
        return User::role(['admin', 'agent'])
            ->orderBy('name')
            ->get();
    }

    #[On('edit-ticket')]
    public function load(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('update', $ticket);

        $this->ticketId = $ticket->id;
        $this->title = $ticket->title;
        $this->description = $ticket->description;
        $this->category_id = (string) $ticket->category_id;
        $this->priority = $ticket->priority;
        $this->status = $ticket->status;
        $this->assigned_to = (string) ($ticket->assigned_to ?? '');
        $this->resetValidation();

        Flux::modal('edit-ticket')->show();
    }

    public function update(): void
    {
        $ticket = Ticket::findOrFail($this->ticketId);

        $this->authorize('update', $ticket);

        if ($this->isStaff) {
            $data = $this->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:5000',
                'category_id' => 'required|exists:categories,id',
                'priority' => 'required|in:' . implode(',', Ticket::PRIORITIES),
                'status' => 'required|in:' . implode(',', Ticket::STATUSES),
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $data['assigned_to'] = $data['assigned_to'] ?: null;
            $data['closed_at'] = $data['status'] === 'closed' ? $ticket->closed_at ?? now() : null;
        } else {
            $data = $this->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:5000',
            ]);
        }

        $ticket->update($data);

        $this->dispatch('ticket-updated');

        Flux::modal('edit-ticket')->close();
        Flux::toast(variant: 'success', text: 'Ticket updated successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('ticketId', 'title', 'description', 'category_id', 'assigned_to');
        $this->priority = 'medium';
        $this->status = 'open';
        $this->resetValidation();
    }

};
?>

<div>
    <form wire:submit="update">
        <flux:modal name="edit-ticket" class="md:w-[32rem]" wire:close="resetForm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit ticket</flux:heading>
                    <flux:text class="mt-2">Update the ticket details below.</flux:text>
                </div>

                <flux:input wire:model="title" label="Title" />

                @if ($this->isStaff)
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:select wire:model="category_id" label="Category">
                            @foreach ($this->categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="priority" label="Priority">
                            @foreach (\App\Models\Ticket::PRIORITIES as $p)
                                <flux:select.option value="{{ $p }}">{{ ucfirst($p) }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="status" label="Status">
                            @foreach (\App\Models\Ticket::STATUSES as $s)
                                <flux:select.option value="{{ $s }}">
                                    {{ str($s)->replace('_', ' ')->title() }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="assigned_to" label="Assignee">
                            <flux:select.option value="">Unassigned</flux:select.option>
                            @foreach ($this->agents as $agent)
                                <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                <flux:textarea wire:model="description" label="Description" rows="5" />

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>
