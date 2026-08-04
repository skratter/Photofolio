@props([
    'title',
    'description' => null,
    'image' => null,
    'type' => 'website',
])

@php
    // Query string included (not just url()->current()) so paginated album
    // views (?seite=2) self-canonicalize instead of all pointing back at page 1.
    $canonicalUrl = url()->current().(request()->getQueryString() ? '?'.request()->getQueryString() : '');
@endphp

<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
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
