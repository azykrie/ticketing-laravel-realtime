<?php

use Livewire\Component;
use App\Models\Category;
use Flux\Flux;

new class extends Component {
    public string $name = '';

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $this->name,
        ]);

        Flux::modal('add-category')->close();

        Flux::toast(variant: 'success', text: 'Category created successfully!');

        $this->reset('name');
        $this->dispatch('category-created');
    }
};
?>

<div>
    <form wire:submit="create">
        <flux:modal :dismissible="false" name="add-category" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create category</flux:heading>
                    <flux:text class="mt-2">Fill in the form below to create a new category.</flux:text>
                </div>
                <flux:input wire:model="name" label="Name" placeholder="Category name" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>
