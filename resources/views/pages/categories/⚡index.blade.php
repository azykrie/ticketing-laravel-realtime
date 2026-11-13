<?php

use App\Models\Category;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new #[Title('Categories')] class extends Component {
use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

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

    #[Computed]
    public function categories()
    {
        return Category::query()->orderBy($this->sortBy, $this->sortDirection)->paginate(5);
    }

    #[On(['category-created', 'category-updated', 'category-deleted'])]
    public function refreshList(): void
    {
        $this->resetPage();
    }
};
?>

<div>


    <div>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">Categories</flux:heading>
                <flux:text class="mt-1">Your latest category activity.</flux:text>
            </div>

            <flux:modal.trigger name="add-category">
                <flux:button icon="plus" class="w-full sm:w-auto">Add Category</flux:button>
            </flux:modal.trigger>
        </div>

    </div>


    <flux:separator variant="subtle" class="my-6" />

    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                wire:click="sort('created_at')">Created</flux:table.column>
            <flux:table.column>Action</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>


                    <flux:table.cell class="whitespace-nowrap">{{ $category->created_at->format('d M Y') }}
                    </flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square"
                                    wire:click="$dispatch('edit-category', { id: {{ $category->id }} })">
                                    Edit
                                </flux:menu.item>

                                <flux:menu.separator />

                                <flux:menu.item icon="trash" variant="danger"
                                    wire:click="$dispatch('delete-category', { id: {{ $category->id }} })">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @livewire('pages::categories.create')
    @livewire('pages::categories.edit')
    @livewire('pages::categories.delete')
</div>
