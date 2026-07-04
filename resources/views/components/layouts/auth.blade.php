<x-layouts.app :title="$title ?? null">
    <div class="flex min-h-screen items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-900">
        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
