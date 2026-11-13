<?php

use App\Models\Ticket;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $toast = true;    // tampilkan toast saat notifikasi masuk
    public bool $compact = false; // tombol hanya ikon (untuk header mobile)

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->limit(10)->get();
    }

    public function getListeners(): array
    {
        return [
            'echo-notification:App.Models.User.' . auth()->id() => 'onNotification',
        ];
    }

    public function onNotification(array $payload = []): void
    {
        $this->refreshData();

        if ($this->toast) {
            Flux::toast(
                heading: 'New notification',
                text: $payload['message'] ?? 'You have a new notification.',
            );
        }
    }

    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->refreshData();

        $ticketId = $notification->data['ticket_id'] ?? null;

        if ($ticketId && Ticket::whereKey($ticketId)->exists()) {
            $this->redirect(route('tickets.show', $ticketId), navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->refreshData();
    }

    protected function refreshData(): void
    {
        unset($this->unreadCount, $this->notifications);
    }
};
?>

<div>
    <flux:dropdown position="top" align="start">
        @if ($compact)
            <flux:button variant="ghost" size="sm" icon="bell" class="relative">
                @if ($this->unreadCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                        {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                    </span>
                @endif
            </flux:button>
        @else
            <flux:button variant="ghost" icon="bell" class="w-full justify-start">
                Notifications
                @if ($this->unreadCount > 0)
                    <flux:badge size="sm" color="red" class="ms-auto">{{ $this->unreadCount }}</flux:badge>
                @endif
            </flux:button>
        @endif

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-2 py-1">
                <flux:heading size="sm">Notifications</flux:heading>
                @if ($this->unreadCount > 0)
                    <flux:button size="xs" variant="ghost" wire:click="markAllAsRead">Mark all as read</flux:button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($this->notifications as $notification)
                <flux:menu.item wire:key="notif-{{ $notification->id }}"
                    wire:click="open('{{ $notification->id }}')">
                    <div class="flex w-full items-start gap-2">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-blue-500' }}"></span>
                        <div class="min-w-0">
                            <div class="whitespace-normal text-sm {{ $notification->read_at ? '' : 'font-medium' }}">
                                {{ $notification->data['message'] ?? 'Notification' }}
                            </div>
                            <div class="text-xs text-zinc-500">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-6 text-center text-sm text-zinc-500">No notifications yet.</div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>