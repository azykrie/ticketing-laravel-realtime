<?php

use App\Models\Category;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $categoryId = null;
    public string $name = '';

    #[On('delete-category')]
    public function confirm(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;

        Flux::modal('delete-category')->show();
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $category = Category::findOrFail($this->categoryId);

        if ($category->tickets()->exists()) {
            $this->resetForm();
            Flux::modal('delete-category')->close();
            Flux::toast(variant: 'danger', text: 'This category is used by tickets and cannot be deleted.');
            return;
        }

        $category->delete();

        $this->resetForm();
        $this->dispatch('category-deleted');

        Flux::modal('delete-category')->close();
        Flux::toast(variant: 'success', text: 'Category deleted successfully!');
    }

    public function resetForm(): void
    {
        $this->reset('categoryId', 'name');
    }
};
?>

<div>
    <flux:modal name="delete-category" class="min-w-[22rem]" wire:close="resetForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete category?</flux:heading>
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
