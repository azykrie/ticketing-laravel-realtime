<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create($data);
        $user->assignRole('user'); 
        
        Auth::login($user);
        session()->regenerate();
        $this->redirect('/dashboard', navigate: true);
    }
};
?>

<div>
    <form wire:submit="register">
        <flux:card size="lg">
            <flux:card.header>
                <flux:card.heading>Create an account</flux:card.heading>
                <flux:card.subheading>Sign up to start using the ticketing system</flux:card.subheading>
            </flux:card.header>

            <flux:card.body class="space-y-6">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="name" autofocus />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password</flux:label>
                    <flux:input wire:model="password" type="password" viewable />
                    <flux:error name="password" />
                    <flux:description>Must be at least 8 characters long.</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Confirm password</flux:label>
                    <flux:input wire:model="password_confirmation" type="password" viewable />
                    <flux:error name="password_confirmation" />
                </flux:field>
            </flux:card.body>

            <flux:card.footer>
                <flux:button type="submit" variant="primary" class="w-full">Sign up</flux:button>
            </flux:card.footer>
        </flux:card>
    </form>

    <flux:text class="mt-4 text-center">
        Already have an account?
        <flux:link :href="route('login')" wire:navigate>Log in</flux:link>
    </flux:text>
</div>