<?php

use App\Models\Category;
use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new #[Title('Tickets')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: '')]
    public string $category = '';

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'priority', 'category'])) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'priority', 'category');
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function tickets()
    {
        $user = auth()->user();

        return Ticket::query()
            ->with(['category', 'creator', 'assignee'])
            // user biasa hanya melihat tiket miliknya
            ->when(!$user->hasAnyRole(['admin', 'agent']), fn($q) => $q->where('created_by', $user->id))
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->priority, fn($q) => $q->where('priority', $this->priority))
            ->when($this->category, fn($q) => $q->where('category_id', $this->category))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[On(['ticket-created', 'ticket-updated', 'ticket-deleted'])]
    public function refreshList(): void
    {
        unset($this->tickets);
    }

    public function getListeners(): array
    {
        if (
            !auth()
                ->user()
                ->hasAnyRole(['admin', 'agent'])
        ) {
            return [];
        }

        return [
            'echo-private:tickets,TicketChanged' => 'refreshFromSocket',
            'echo-private:tickets,.TicketChanged' => 'refreshFromSocket',
        ];
    }

    public function refreshFromSocket(): void
    {
        unset($this->tickets);
    }
};
?>

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Tickets</flux:heading>
            <flux:text class="mt-1">Track and manage support tickets.</flux:text>
        </div>

        <flux:modal.trigger name="create-ticket">
            <flux:button icon="plus" variant="primary" class="w-full sm:w-auto">New Ticket</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator variant="subtle" class="my-6" />

    {{-- Filters --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search title or code..." clearable />
        </div>

        <flux:select wire:model.live="status" placeholder="All status">
            <flux:select.option value="">All status</flux:select.option>
            @foreach (\App\Models\Ticket::STATUSES as $s)
                <flux:select.option value="{{ $s }}">{{ str($s)->replace('_', ' ')->title() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="priority" placeholder="All priority">
            <flux:select.option value="">All priority</flux:select.option>
            @foreach (\App\Models\Ticket::PRIORITIES as $p)
                <flux:select.option value="{{ $p }}">{{ ucfirst($p) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="category" placeholder="All category">
            <flux:select.option value="">All category</flux:select.option>
            @foreach ($this->categories as $c)
                <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($search || $status || $priority || $category)
        <div class="mb-4">
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear filters
            </flux:button>
        </div>
    @endif

    {{-- Table --}}
    <flux:table :paginate="$this->tickets">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection"
                wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection"
                wire:click="sort('title')">Title</flux:table.column>
            <flux:table.column>Category</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'priority'" :direction="$sortDirection"
                wire:click="sort('priority')">Priority</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column>Requester</flux:table.column>
            <flux:table.column>Assignee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                wire:click="sort('created_at')">Created</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->tickets as $ticket)
                <flux:table.row :key="$ticket->id">
                    <flux:table.cell class="whitespace-nowrap">
                        <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->code }}</flux:link>
                    </flux:table.cell>

                    <flux:table.cell variant="strong" class="max-w-xs truncate">{{ $ticket->title }}</flux:table.cell>

                    <flux:table.cell>{{ $ticket->category?->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->priorityColor()">{{ ucfirst($ticket->priority) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->statusColor()">
                            {{ str($ticket->status)->replace('_', ' ')->title() }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $ticket->creator?->name }}</flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $ticket->assignee?->name ?? '-' }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $ticket->created_at->format('d M Y') }}
                    </flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                            <flux:menu>
                                <flux:menu.item icon="eye" :href="route('tickets.show', $ticket)" wire:navigate>
                                    View
                                </flux:menu.item>

                                <flux:menu.item icon="pencil-square"
                                    wire:click="$dispatch('edit-ticket', { id: {{ $ticket->id }} })">
                                    Edit
                                </flux:menu.item>

                                @role('admin')
                                    <flux:menu.separator />

                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:click="$dispatch('delete-ticket', { id: {{ $ticket->id }} })">
                                        Delete
                                    </flux:menu.item>
                                @endrole
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="py-8 text-center">
                        <flux:text>No tickets found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    @livewire('pages::tickets.create')
    @livewire('pages::tickets.edit')
    @livewire('pages::tickets.delete')
</div>
