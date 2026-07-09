@props(['url', 'label'])

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
        <svg viewBox="0 0 24 24" class="size-4 fill-current" aria-label="{{ $label }}">
            {{ $slot }}
        </svg>
    </a>
@endif
