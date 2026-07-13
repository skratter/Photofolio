@props([
    'title',
    'description' => null,
    'image' => null,
    'type' => 'website',
])

<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:title" content="{{ $title }}">

@if ($description)
    <meta property="og:description" content="{{ $description }}">
    <meta name="twitter:description" content="{{ $description }}">
@endif

@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif
