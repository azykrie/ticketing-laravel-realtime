<?php

use App\Models\Ticket;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $userId = null;
    public string $name = '';

    #[On('delete-user')]
    public function confirm(int $id): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;

        Flux::modal('delete-user')->show();
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $user = User::findOrFail($this->userId);

        if ($user->id === auth()->id()) {
            Flux::modal('delete-user')->close();
            Flux::toast(variant: 'danger', text: 'You cannot delete your own account.');
            return;
        }

        if (Ticket::where('created_by', $user->id)->exists()) {
            Flux::modal('delete-user')->close();
            Flux::toast(variant: 'danger', text: 'This user has tickets and cannot be deleted.');
            return;
        }

        $user->delete();

        $this->resetForm();
        $this->dispatch('user-deleted');

        Flux::modal('delete-user')->close();
        Flux::toast(variant: 'success', text: 'User deleted successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('userId', 'name');
    }
};
?>

<div>
    <flux:modal name="delete-user" class="min-w-[22rem]" wire:close="resetForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete user?</flux:heading>
                <flux:text class="mt-2">
                    You are about to delete <strong>{{ $name }}</strong>.
                    This action cannot be undone.
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