{{-- resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/favicons/favicon-48.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/favincon-512.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance

    {{ $head ?? '' }}
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
    {{ $slot }}

    <flux:toast />

    @livewireScripts
    @fluxScripts
</body>

</html>