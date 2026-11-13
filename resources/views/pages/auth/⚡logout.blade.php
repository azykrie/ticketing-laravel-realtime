<?php

use Livewire\Component;

new class extends Component
{
    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/login', navigate: true);
    }
};
?>

<div>
    <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
</div>