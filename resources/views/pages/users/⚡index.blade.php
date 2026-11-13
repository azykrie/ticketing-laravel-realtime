<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Users')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role'])) {
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

    #[On(['user-created', 'user-updated', 'user-deleted'])]
    public function refreshList(): void
    {
        unset($this->users);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->role, fn ($q) => $q->role($this->role))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }
};
?>

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Users</flux:heading>
            <flux:text class="mt-1">Manage user accounts and their roles.</flux:text>
        </div>

        <flux:modal.trigger name="create-user">
            <flux:button icon="plus" variant="primary" class="w-full sm:w-auto">Add User</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator variant="subtle" class="my-6" />

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search name or email..." clearable />
        </div>

        <flux:select wire:model.live="role">
            <flux:select.option value="">All roles</flux:select.option>
            @foreach ($this->roles as $r)
                <flux:select.option value="{{ $r->name }}">{{ ucfirst($r->name) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection"
                wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                wire:click="sort('created_at')">Created</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong" class="whitespace-nowrap">
                        {{ $user->name }}
                        @if ($user->id === auth()->id())
                            <flux:badge size="sm" color="zinc" class="ms-1">You</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>{{ $user->email }}</flux:table.cell>

                    <flux:table.cell>
                        @php($roleName = $user->roles->first()?->name)
                        <flux:badge size="sm"
                            :color="match ($roleName) { 'admin' => 'red', 'agent' => 'blue', default => 'zinc' }">
                            {{ $roleName ? ucfirst($roleName) : 'No role' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square"
                                    wire:click="$dispatch('edit-user', { id: {{ $user->id }} })">
                                    Edit
                                </flux:menu.item>

                                @if ($user->id !== auth()->id())
                                    <flux:menu.separator />

                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:click="$dispatch('delete-user', { id: {{ $user->id }} })">
                                        Delete
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-8 text-center">
                        <flux:text>No users found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @livewire('pages::users.create')
    @livewire('pages::users.edit')
    @livewire('pages::users.delete')
</div>