<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth'), Title('Login')] class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $key = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");
            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60); // hitungan hangus setelah 60 detik
            $this->addError('email', 'Email or password is incorrect.');
            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $this->redirectIntended('/dashboard', navigate: true);
    }
};
?>

<div>
    <form wire:submit="login">
        <flux:card size="lg">
            <flux:card.header>
                <flux:card.heading>Login</flux:card.heading>
                <flux:card.subheading>Login to your account</flux:card.subheading>
            </flux:card.header>

            <flux:card.body class="space-y-6">
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" autofocus />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password</flux:label>
                    <flux:input wire:model="password" type="password" viewable />
                    <flux:error name="password" />
                </flux:field>

                <flux:checkbox wire:model="remember" label="Remember me" />
            </flux:card.body>

            <flux:card.footer>
                <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
            </flux:card.footer>
        </flux:card>
    </form>

    <flux:text class="mt-4 text-center">
        Not registered yet?
        <flux:link :href="route('register')" wire:navigate>Register</flux:link>
    </flux:text>
</div>
