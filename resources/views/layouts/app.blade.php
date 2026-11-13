<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @fluxAppearance
</head>

<body class="min-h-dvh bg-zinc-50 dark:bg-zinc-900">
    <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900">
        <flux:sidebar.header>

            <flux:sidebar.brand :href="route('dashboard')" name="Ticketing" logo="https://fluxui.dev/img/demo/logo.png"
                logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png" />

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate>
                Dashboard
            </flux:sidebar.item>

            <flux:sidebar.item icon="ticket" :href="route('tickets.index')" :current="request()->routeIs('tickets.*')"
                wire:navigate>
                Tickets
            </flux:sidebar.item>

            <flux:sidebar.spacer />

            @role('admin')
                <flux:sidebar.group heading="Administration" class="grid">
                    <flux:sidebar.item icon="tag" :href="route('categories.index')"
                        :current="request()->routeIs('categories.*')" wire:navigate>
                        Categories
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')"
                        wire:navigate>
                        Users
                    </flux:sidebar.item>
                </flux:sidebar.group>
            @endrole
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="question-mark-circle" :href="route('help')" :current="request()->routeIs('help')"
                wire:navigate>
                How to Use
            </flux:sidebar.item>
        </flux:sidebar.nav>

        @livewire('pages::notifications.bell', [], key('bell-sidebar'))

        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:sidebar.profile
                :initials="auth()->user()->name ? \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') : ''"
                :name="auth()->user()->name" />

            <flux:menu>
                <flux:menu.radio.group>
                    <flux:menu.radio checked>{{ auth()->user()->name }}</flux:menu.radio>
                </flux:menu.radio.group>

                <flux:menu.separator />

                @livewire('pages::auth.logout')
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:header class="lg:hidden bg-white dark:bg-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="start">
            <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />

            <flux:menu>
                <flux:menu.radio.group>
                    <flux:menu.radio checked>{{ auth()->user()->name }}</flux:menu.radio>
                </flux:menu.radio.group>

                <flux:menu.separator />


            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main inset class="lg:ms-0">
        {{ $slot }}
    </flux:main>

    <flux:toast />

    @livewireScripts
    @fluxScripts

    @persist('toast')
        <flux:toast />
    @endpersist
</body>

</html>
