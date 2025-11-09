<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-b from-rose-100 to-rose-200">
    <div class="bg-white/90 shadow-md rounded-2xl w-[400px] p-8">
        <div class="flex flex-col items-center">
            <div class="bg-rose-400 rounded-full p-4 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 18a8 8 0 1116 0H2z" />
                </svg>
            </div>
            <h2 class="text-2xl font-semibold text-rose-600 mb-6">Sign Up</h2>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-rose-700">Full Name</label>
                <input type="text" id="name" name="name"
                       class="mt-1 block w-full border-b border-rose-300 bg-transparent focus:border-rose-500 focus:ring-0 outline-none"
                       placeholder="Your name" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-rose-700">Email</label>
                <input type="email" id="email" name="email"
                       class="mt-1 block w-full border-b border-rose-300 bg-transparent focus:border-rose-500 focus:ring-0 outline-none"
                       placeholder="example@email.com" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-rose-700">Password</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full border-b border-rose-300 bg-transparent focus:border-rose-500 focus:ring-0 outline-none"
                       placeholder="********" required>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-rose-700">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="mt-1 block w-full border-b border-rose-300 bg-transparent focus:border-rose-500 focus:ring-0 outline-none"
                       placeholder="********" required>
            </div>

            <button type="submit"
                class="w-full py-2 bg-rose-500 text-white font-semibold rounded-full hover:bg-rose-600 transition">
                Sign Up
            </button>

            <p class="text-center text-sm text-gray-600 mt-6">
                Already have an account?
                <a href="{{ route('login') }}" class="text-rose-600 hover:underline font-semibold">Sign In</a>
            </p>
        </form>
    </div>
</body>
</html>
