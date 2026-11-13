<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'user';

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[On('edit-user')]
    public function load(int $id): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'user';
        $this->resetValidation();

        Flux::modal('edit-user')->show();
    }

    public function update(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $user = User::findOrFail($this->userId);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        // admin tidak boleh menurunkan role dirinya sendiri
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            $this->addError('role', 'You cannot change your own role.');
            return;
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        $this->dispatch('user-updated');

        Flux::modal('edit-user')->close();
        Flux::toast(variant: 'success', text: 'User updated successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('userId', 'name', 'email', 'password');
        $this->role = 'user';
        $this->resetValidation();
    }
};
?>

<div>
    <form wire:submit="update">
        <flux:modal name="edit-user" class="md:w-96" wire:close="resetForm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit user</flux:heading>
                    <flux:text class="mt-2">Update account details and role.</flux:text>
                </div>

                <flux:input wire:model="name" label="Name" />
                <flux:input wire:model="email" label="Email" type="email" />
                <flux:input wire:model="password" label="New password" type="password" viewable
                    description:trailing="Leave empty to keep the current password." />

                <flux:select wire:model="role" label="Role">
                    @foreach ($this->roles as $r)
                        <flux:select.option value="{{ $r->name }}">{{ ucfirst($r->name) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>