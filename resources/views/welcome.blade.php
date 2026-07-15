<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SLT WhatsApp') }} - Business Messaging Made Simple</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slt-ink min-h-screen">
        <!-- Navigation -->
        <nav class="border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center">
                        <a href="/" class="flex min-w-0 items-center gap-3">
                            <img src="{{ asset('images/slt-logo.png') }}" alt="SLT" class="h-10 w-auto" />
                            <span class="text-white font-semibold text-base sm:text-lg truncate">SLT WhatsApp</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        @auth
                            <a href="{{ url('/chats') }}" class="px-4 sm:px-5 py-2 rounded-xl text-sm sm:text-base bg-slt-primary text-white hover:bg-slt-primary-600 transition-all whitespace-nowrap">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm sm:text-base text-slt-muted hover:text-white transition-all whitespace-nowrap">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-4 sm:px-5 py-2 rounded-xl text-sm sm:text-base bg-slt-primary text-white hover:bg-slt-primary-600 transition-all whitespace-nowrap">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slt-accent/10 border border-slt-accent/20 mb-8">
                        <span class="w-2 h-2 rounded-full bg-slt-accent"></span>
                        <span class="text-slt-accent text-sm font-medium">Powered by SLT-Mobitel</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">
                        Business Messaging
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-slt-info via-slt-primary to-slt-accent">
                            Made Simple
                        </span>
                    </h1>

                    <p class="text-lg text-slt-muted mb-8 max-w-lg">
                        Connect with your customers through WhatsApp Business API. Send messages, manage conversations, and grow your business with Sri Lanka's leading telecom provider.
                    </p>

                    <div class="flex flex-wrap gap-4 mb-12">
                        @auth
                            <a href="{{ url('/chats') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slt-accent text-white hover:opacity-90 transition-all font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Open Chats
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slt-accent text-white hover:opacity-90 transition-all font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Open Chats
                            </a>
                        @endauth
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-8">
                        <div>
                            <div class="text-3xl font-bold text-white">99.9%</div>
                            <div class="text-slt-muted text-sm">Uptime</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-white">24/7</div>
                            <div class="text-slt-muted text-sm">Support</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-white">10K+</div>
                            <div class="text-slt-muted text-sm">Messages/Day</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Chat Preview -->
                <div class="relative">
                    <div class="rounded-2xl bg-white/5 border border-white/10 overflow-hidden shadow-slt">
                        <!-- Chat Header -->
                        <div class="p-4 border-b border-white/10 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slt-info flex items-center justify-center text-white font-semibold">
                                C
                            </div>
                            <div>
                                <div class="font-medium text-white">Customer Support</div>
                                <div class="flex items-center gap-1 text-xs text-slt-accent">
                                    <span class="w-2 h-2 rounded-full bg-slt-accent"></span>
                                    Online
                                </div>
                            </div>
                        </div>

                        <!-- Chat Messages -->
                        <div class="p-4 space-y-4 bg-slt-ink/50 min-h-[240px] sm:min-h-[300px]">
                            <!-- Incoming message -->
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-2xl px-4 py-2 bg-white/10 text-white">
                                    <div>Hi! How can I help you today?</div>
                                    <div class="text-[11px] text-slt-muted mt-1 text-right">09:30 AM</div>
                                </div>
                            </div>

                            <!-- Outgoing message -->
                            <div class="flex justify-end">
                                <div class="max-w-[75%] rounded-2xl px-4 py-2 bg-slt-primary text-white">
                                    <div>I'd like to know about your business plans</div>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <span class="text-[11px] opacity-70">09:31 AM</span>
                                        <svg class="w-3 h-3 opacity-70" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Incoming message -->
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-2xl px-4 py-2 bg-white/10 text-white">
                                    <div>Great choice! We have several packages starting from Rs. 2,500/month with unlimited messaging.</div>
                                    <div class="text-[11px] text-slt-muted mt-1 text-right">09:32 AM</div>
                                </div>
                            </div>

                            <!-- Outgoing message -->
                            <div class="flex justify-end">
                                <div class="max-w-[75%] rounded-2xl px-4 py-2 bg-slt-primary text-white">
                                    <div>That sounds perfect! Sign me up</div>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <span class="text-[11px] opacity-70">09:33 AM</span>
                                        <svg class="w-3 h-3 opacity-70" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="p-4 border-t border-white/10 bg-white/5">
                            <div class="flex gap-3">
                                <input type="text" placeholder="Type a message..." disabled
                                    class="flex-1 rounded-2xl bg-white/5 border-white/10 text-white placeholder-slt-muted" />
                                <button class="px-4 py-2 rounded-2xl bg-slt-accent text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="border-t border-white/10 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <div class="w-12 h-12 rounded-xl bg-slt-info/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-slt-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Auto-Sync Messaging</h3>
                        <p class="text-slt-muted text-sm">Messages sync automatically every 5 seconds, keeping your conversations up to date in real-time.</p>
                    </div>

                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <div class="w-12 h-12 rounded-xl bg-slt-accent/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-slt-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Multi-Admin Chat Lock</h3>
                        <p class="text-slt-muted text-sm">Prevent message conflicts with intelligent chat locking. Only one admin can respond at a time.</p>
                    </div>

                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <div class="w-12 h-12 rounded-xl bg-slt-primary/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-slt-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">API Logging</h3>
                        <p class="text-slt-muted text-sm">Track all API calls with detailed logs including response times, status codes, and request data.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-white/10 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/slt-logo.png') }}" alt="SLT" class="h-8 w-auto" />
                        <span class="text-slt-muted text-sm">SLT WhatsApp Business </span>
                    </div>
                    <div class="text-slt-muted text-sm text-center sm:text-right">
                        &copy; {{ date('Y') }} SLT-Digital platform. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
