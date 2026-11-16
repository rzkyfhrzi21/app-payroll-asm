<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">

    @php
        $pageTitle = html_entity_decode((string) ($title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    @endphp

    <title>{{ $pageTitle !== '' ? $pageTitle.' — ' : '' }}{{ !empty($dataPerusahaan?->kode_perusahaan) ? $dataPerusahaan->kode_perusahaan.' • ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ !empty($dataPerusahaan?->url_logo) ? $dataPerusahaan->url_logo : asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas-bg font-sans text-slate-text-primary antialiased">
    <x-ui.toast-stack />
    <x-ui.lightbox />
    {{ $slot }}
</body>
</html>
