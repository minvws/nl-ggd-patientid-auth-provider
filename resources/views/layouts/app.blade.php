<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="robots" content="noindex,nofollow">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @hasSection('page-title')
        <title>@yield('page-title') - {{ config('app.name', '') }}</title>
        @else
        <title>{{ config('app.name', '') }}</title>
        @endif
        <link rel="stylesheet" href="/css/app.css">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="mask-icon" href="/mask-icon.svg" color="#000099">
        <link rel="shortcut icon" href="/favicon.ico">
        <meta name="theme-color" content="#ffffff">
    </head>
    <body>
        <x-header/>
        <main tabindex="-1">
            @yield('content')
        </main>
        <x-footer/>
    </body>
</html>
