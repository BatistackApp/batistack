<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />

        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>{{ config('app.name') }}</title>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

        @filamentStyles
        @vite('resources/css/app.css')
    </head>

    <body class="bg-gray-50 font-sans antialiased">
        <x-site.header />

        <main>
            {{ $slot }}
        </main>

        <x-site.footer />

        @livewire('notifications')

        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js" />
        @filamentScripts
        @vite('resources/js/app.js')
    </body>
</html>
