<x-slot:head>
    @if ($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
</x-slot:head>

<div class="mx-auto max-w-3xl px-6 py-16">
    <flux:heading size="xl">{{ $page->title }}</flux:heading>

    <div class="page-content mt-8">
        {!! $page->content !!}
    </div>

    @if ($this->downloads->isNotEmpty())
        <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-700">
            <flux:heading size="lg" class="mb-4">Downloads</flux:heading>

            <ul class="space-y-2">
                @foreach ($this->downloads as $attachment)
                    <li>
                        <flux:link href="{{ $attachment->url() }}" download="{{ $attachment->original_filename }}">
                            {{ $attachment->original_filename }}
                        </flux:link>
                        <span class="text-zinc-500">({{ $attachment->formattedSize() }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
