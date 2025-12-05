<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                /* Sama persis seperti background login */
                background: linear-gradient(to bottom, #ffe4e6, #fecdd3); /* from-rose-100 ke rose-200 */
                min-height: 100vh;
            }
        </style>
    </head>

    <body class="font-sans text-gray-900 antialiased">

        {{-- Container tengah --}}
        <div class="min-h-screen flex flex-col justify-center items-center px-4">

            {{-- Slot halaman (card) --}}
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>

        </div>
    </body>
</html>
