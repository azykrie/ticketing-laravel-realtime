<?php

use App\Models\Category;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $categoryId = null;
    public string $name = '';

    #[On('edit-category')]
    public function load(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->resetValidation();

        Flux::modal('edit-category')->show();
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $this->categoryId,
        ]);

        Category::findOrFail($this->categoryId)->update(['name' => $this->name]);

        $this->dispatch('category-updated');

        Flux::modal('edit-category')->close();
        Flux::toast(variant: 'success', text: 'Category updated successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('name', 'categoryId');
        $this->resetValidation();
    }
};
?>

<div>
    <form wire:submit="update">
        <flux:modal name="edit-category" class="md:w-96" wire:close="resetForm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit category</flux:heading>
                    <flux:text class="mt-2">Update the category name below.</flux:text>
                </div>

                <flux:input wire:model="name" label="Name" placeholder="Category name" />

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>