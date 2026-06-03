<section>
    <header class="flex items-start sm:items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-slt-primary/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-slt-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-medium text-white">
                {{ __('Update Password') }}
            </h2>
            <p class="text-sm text-slt-muted">
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            </p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" name="current_password" type="password"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   autocomplete="current-password" />
            @error('current_password', 'updatePassword')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-slt-muted mb-2">{{ __('New Password') }}</label>
            <input id="update_password_password" name="password" type="password"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   autocomplete="new-password" />
            @error('password', 'updatePassword')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   autocomplete="new-password" />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 pt-2">
            <button type="submit" class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl bg-slt-accent text-white hover:opacity-90 transition-all">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slt-accent"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
