<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SLT WhatsApp') }} - Super Admin Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" style="background-color: #0b1e2d; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="background-color: #13293d; border-radius: 12px; padding: 2rem; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
        <div class="text-center mb-6">
            <h2 class="text-xl font-semibold text-white mb-2">Super Admin Login</h2>
            <p class="text-gray-300 text-sm">Access the Super Admin panel</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 p-3 rounded-lg bg-green-600/20 border border-green-600/30 text-green-300 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       style="background-color: #1c3b50; border: 1px solid #2a4a5e; color: white; border-radius: 8px; width: 100%; padding: 0.75rem; font-size: 0.875rem;"
                       placeholder="Enter your email" required autofocus autocomplete="username" />
                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <input id="password" type="password" name="password"
                       style="background-color: #1c3b50; border: 1px solid #2a4a5e; color: white; border-radius: 8px; width: 100%; padding: 0.75rem; font-size: 0.875rem;"
                       placeholder="Enter your password" required autocomplete="current-password" />
                @error('password')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full py-3 rounded-lg font-medium text-white transition-all" style="background-color: #4CAF50;">
                Log in
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-gray-300">Back to regular login</a>
        </div>
    </div>
</body>
</html>