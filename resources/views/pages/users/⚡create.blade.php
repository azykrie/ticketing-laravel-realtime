<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'user';

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // di-hash oleh cast 'hashed' di model User
        ]);

        $user->syncRoles([$data['role']]);

        $this->resetForm();
        $this->dispatch('user-created');

        Flux::modal('create-user')->close();
        Flux::toast(variant: 'success', text: 'User created successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('name', 'email', 'password');
        $this->role = 'user';
        $this->resetValidation();
    }
};
?>

<div>
    <form wire:submit="save">
        <flux:modal name="create-user" class="md:w-96" wire:close="resetForm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add user</flux:heading>
                    <flux:text class="mt-2">Create a new account and choose its role.</flux:text>
                </div>

                <flux:input wire:model="name" label="Name" />
                <flux:input wire:model="email" label="Email" type="email" />
                <flux:input wire:model="password" label="Password" type="password" viewable
                    description:trailing="At least 8 characters." />

                <flux:select wire:model="role" label="Role">
                    @foreach ($this->roles as $r)
                        <flux:select.option value="{{ $r->name }}">{{ ucfirst($r->name) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>