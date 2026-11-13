<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    protected function baseQuery()
    {
        $user = auth()->user();

        return Ticket::query()->when(
            ! $user->hasAnyRole(['admin', 'agent']),
            fn ($q) => $q->where('created_by', $user->id)
        );
    }

    #[Computed]
    public function stats(): array
    {
        $counts = $this->baseQuery()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => $counts->sum(),
            'open' => $counts['open'] ?? 0,
            'in_progress' => $counts['in_progress'] ?? 0,
            'resolved' => $counts['resolved'] ?? 0,
            'closed' => $counts['closed'] ?? 0,
        ];
    }

    #[Computed]
    public function assignedToMe(): int
    {
        return Ticket::where('assigned_to', auth()->id())
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
    }

    #[Computed]
    public function latest()
    {
        return $this->baseQuery()
            ->with(['category', 'creator'])
            ->latest()
            ->limit(5)
            ->get();
    }

    public function getListeners(): array
    {
        if (! auth()->user()->hasAnyRole(['admin', 'agent'])) {
            return [];
        }

        return [
            'echo-private:tickets,TicketChanged' => 'refreshFromSocket',
            'echo-private:tickets,.TicketChanged' => 'refreshFromSocket',
        ];
    }

    public function refreshFromSocket(): void
    {
        unset($this->stats, $this->latest, $this->assignedToMe);
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Welcome, {{ auth()->user()->name }}</flux:heading>
    <flux:text class="mt-1">Here is an overview of your tickets.</flux:text>

    <flux:separator variant="subtle" class="my-6" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card>
            <flux:text size="sm">Total</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['total'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text size="sm">Open</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['open'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text size="sm">In Progress</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['in_progress'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text size="sm">Resolved</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['resolved'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text size="sm">Closed</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['closed'] }}</flux:heading>
        </flux:card>
    </div>

    @role('admin|agent')
        <flux:callout icon="user-circle" class="mt-4"
            heading="{{ $this->assignedToMe }} active ticket(s) assigned to you" />
    @endrole

    <div class="mt-8 flex items-center justify-between">
        <flux:heading size="lg">Latest tickets</flux:heading>
        <flux:button size="sm" variant="ghost" :href="route('tickets.index')" wire:navigate>View all</flux:button>
    </div>

    <flux:table class="mt-3">
        <flux:table.columns>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->latest as $ticket)
                <flux:table.row :key="$ticket->id">
                    <flux:table.cell>
                        <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->code }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell variant="strong" class="max-w-xs truncate">{{ $ticket->title }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->statusColor()">
                            {{ str($ticket->status)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $ticket->created_at->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="py-8 text-center">
                        <flux:text>No tickets yet.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>