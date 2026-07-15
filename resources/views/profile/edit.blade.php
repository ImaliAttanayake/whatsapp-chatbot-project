<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            <!-- Profile Header -->
            <div class="p-4 sm:p-6 rounded-2xl bg-white/5 border border-white/10">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-slt-info flex items-center justify-center text-white text-2xl font-semibold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-semibold text-white">Profile Settings</h1>
                        <p class="text-slt-muted">Manage your account settings and preferences</p>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="p-4 sm:p-6 rounded-2xl bg-white/5 border border-white/10">
                @include('profile.partials.update-profile-information-form')
            </div>

            <!-- Update Password -->
            <div class="p-4 sm:p-6 rounded-2xl bg-white/5 border border-white/10">
                @include('profile.partials.update-password-form')
            </div>

            <!-- Delete Account -->
            <div class="p-4 sm:p-6 rounded-2xl bg-white/5 border border-white/10">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
