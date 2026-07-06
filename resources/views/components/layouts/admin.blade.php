<x-layouts.app :title="$title ?? null">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand href="{{ route('admin.dashboard') }}" name="{{ config('app.name') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item
                icon="home"
                href="{{ route('admin.dashboard') }}"
                :current="request()->routeIs('admin.dashboard')"
                wire:navigate
            >
                Dashboard
            </flux:sidebar.item>
            <flux:sidebar.item
                icon="rectangle-stack"
                href="{{ route('admin.albums.index') }}"
                :current="request()->routeIs('admin.albums.*')"
                wire:navigate
            >
                Alben
            </flux:sidebar.item>
            <flux:sidebar.item
                icon="chart-bar"
                href="{{ route('admin.analytics') }}"
                :current="request()->routeIs('admin.analytics')"
                wire:navigate
            >
                Auswertung
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:sidebar.profile initials="{{ auth()->user()->initials() }}" name="{{ auth()->user()->name }}" />
            <flux:menu>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">
                        Abmelden
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="top" align="start">
            <flux:profile initials="{{ auth()->user()->initials() }}" name="{{ auth()->user()->name }}" />
            <flux:menu>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">
                        Abmelden
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main container>
        {{ $slot }}
    </flux:main>
</x-layouts.app>
