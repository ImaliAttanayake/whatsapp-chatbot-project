<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SLT WhatsApp') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slt-ink">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-6 sm:pt-0">
            <div class="mb-6">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('images/slt-logo.png') }}" alt="SLT" class="h-12 w-auto" />
                    <span class="text-white font-semibold text-lg sm:text-xl">SLT WhatsApp</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-5 sm:px-6 py-7 sm:py-8 rounded-2xl bg-white/5 border border-white/10 overflow-hidden">
                {{ $slot }}
            </div>

            <div class="mt-6 text-center text-sm text-slt-muted">
                &copy; {{ date('Y') }} SLT-Digital platform. All rights reserved.
            </div>
        </div>
    </body>
</html>
