<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Support\StoreAttachments;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public string $category_id = '';
    public string $priority = 'medium';
    public array $files = [];

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:' . implode(',', Ticket::PRIORITIES),
            'files' => 'array|max:5',
            'files.*' => 'file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $ticket = Ticket::create([
            ...collect($data)->except('files')->all(),
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        StoreAttachments::handle($ticket, $this->files, auth()->id());

        $this->resetForm();
        $this->dispatch('ticket-created');

        Flux::modal('create-ticket')->close();
        Flux::toast(variant: 'success', text: 'Ticket created successfully!');
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function resetForm(): void
    {
        $this->reset('title', 'description', 'category_id', 'files');
        $this->priority = 'medium';
        $this->resetValidation();
    }
};
?>

<div>
    <form wire:submit="save">
        <flux:modal :dismissible="false" name="create-ticket" class="md:w-[32rem]" wire:close="resetForm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create ticket</flux:heading>
                    <flux:text class="mt-2">Describe the problem and we will help as soon as possible.</flux:text>
                </div>

                <flux:input wire:model="title" label="Title" placeholder="Short summary of the problem" />

                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:select wire:model="category_id" label="Category" placeholder="Choose category...">
                        @foreach ($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority" label="Priority">
                        @foreach (\App\Models\Ticket::PRIORITIES as $p)
                            <flux:select.option value="{{ $p }}">{{ ucfirst($p) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" label="Description" rows="5"
                    placeholder="Explain the problem in detail..." />

                <div>
                    <flux:input type="file" wire:model="files" label="Attachments" multiple
                        description:trailing="Images or documents, max 5 files, 5 MB each." />

                    <div wire:loading wire:target="files" class="mt-1 text-sm text-zinc-500">Uploading...</div>

                    @error('files') <flux:text class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror
                    @error('files.*') <flux:text class="mt-1 text-red-500">{{ $message }}</flux:text> @enderror

                    @if ($files)
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($files as $i => $file)
                                <li wire:key="file-{{ $i }}"
                                    class="flex items-center justify-between rounded border border-zinc-200 px-2 py-1 dark:border-zinc-700">
                                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                    <flux:button size="xs" variant="ghost" icon="x-mark"
                                        wire:click="removeFile({{ $i }})" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Submit Ticket</flux:button>
                </div>
            </div>
        </flux:modal>
    </form>
</div>