<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <!-- Welcome Card -->
            <div class="rounded-2xl bg-white/5 border border-white/10 p-4 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-slt-accent/20 flex items-center justify-center">
                        <svg class="w-7 h-7 text-slt-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-semibold text-white">Welcome back, {{ Auth::user()->name }}!</h1>
                        <p class="text-slt-muted">You're successfully logged in to SLT WhatsApp</p>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <a href="{{ route('chats.index') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-slt-info/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slt-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <span class="text-white font-medium">Chats</span>
                        </div>
                        <p class="text-sm text-slt-muted">View and manage your WhatsApp conversations</p>
                    </a>

                    <a href="{{ route('logs.index') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-slt-primary/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slt-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-white font-medium">API Logs</span>
                        </div>
                        <p class="text-sm text-slt-muted">Monitor API calls and response times</p>
                    </a>

                    <a href="{{ route('profile.edit') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all group">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-slt-accent/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slt-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <span class="text-white font-medium">Profile</span>
                        </div>
                        <p class="text-sm text-slt-muted">Manage your account settings</p>
                    </a>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="rounded-2xl bg-white/5 border border-white/10 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Quick Tips</h2>
                <ul class="space-y-3 text-sm text-slt-muted">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-slt-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Messages auto-sync every 5 seconds when viewing a chat</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-slt-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Chat locking prevents multiple admins from replying simultaneously</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-slt-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Use "Sync Inbox" to discover new conversations from the WhatsApp API</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
