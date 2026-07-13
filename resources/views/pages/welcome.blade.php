{{-- resources/views/welcome.blade.php --}}
<x-layouts.public>
    <x-slot:head>
        @if ($siteSettings->homepage_meta_description)
            <meta name="description" content="{{ $siteSettings->homepage_meta_description }}">
        @endif
        <x-open-graph
            :title="$siteSettings->site_name"
            :description="$siteSettings->homepage_meta_description"
            :image="($cover = $homepageAlbum?->effectiveCoverPhoto()) && $cover->isProcessed() ? route('albums.photos.display', [$homepageAlbum, $cover]) : null"
        />
    </x-slot:head>

    @if ($homepageAlbum)
        <div class="mx-auto max-w-5xl px-6 pt-8 pb-16">
            <x-photo-masonry :album="$homepageAlbum" :photos="$homepagePhotos" :pool="$homepagePhotoPool" :rotate-seconds="$homepageRotateSeconds" />
        </div>
    @else
        <div class="flex items-center justify-center px-6 py-24">
            <div class="rounded-lg border-2 border-dashed border-neutral-500 px-12 py-16 text-center font-mono text-2xl text-neutral-500">
                soon&trade; | {{ $siteSettings->site_name }}
            </div>
        </div>
    @endif
</x-layouts.public>
