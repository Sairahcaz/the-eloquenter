<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Social share cards: X/OG render the hero image under shared links --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="The Eloquenter">
        <meta property="og:title" content="The Eloquenter: Learn Eloquent, gamified">
        <meta property="og:description" content="Master Laravel Eloquent relationships by connecting real database tables, level by level.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('images/eloquenter-hero.jpg') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="The Eloquenter: Learn Eloquent, gamified">
        <meta name="twitter:description" content="Master Laravel Eloquent relationships by connecting real database tables, level by level.">
        <meta name="twitter:image" content="{{ asset('images/eloquenter-hero.jpg') }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
