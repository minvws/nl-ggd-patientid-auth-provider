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
        <link rel="preload" media="screen and (min-width: 768px)" href="/huisstijl/img/ro-logo.svg" as="image">
        <link rel="preload" href="/huisstijl/fonts/RO-SansWebText-Regular.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/huisstijl/fonts/RO-SansWebText-Bold.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="{{ url('huisstijl/css/app.css') }}">
        <link rel="stylesheet" href="{{ url('/css/app.css') }}">
        <link href="/huisstijl/img/favicon.ico" rel="shortcut icon">
    </head>
    <body>
        <main id="main-content" tabindex="-1">

            <h1>{{__("hello world") }}</h1>

            @yield('content')
        </main>
    </body>
</html>
