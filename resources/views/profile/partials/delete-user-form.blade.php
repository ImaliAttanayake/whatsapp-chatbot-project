<section class="space-y-6">
    <header class="flex items-start sm:items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-medium text-white">
                {{ __('Delete Account') }}
            </h2>
            <p class="text-sm text-slt-muted">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
            </p>
        </div>
    </header>

    <p class="text-sm text-slt-muted">
        {{ __('Before deleting your account, please download any data or information that you wish to retain.') }}
    </p>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="px-4 py-2.5 rounded-xl bg-red-500 text-white hover:bg-red-600 transition-all"
    >{{ __('Delete Account') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-slt-ink">
            @csrf
            @method('delete')

            <div class="flex items-start sm:items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-lg font-medium text-white">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>
            </div>

            <p class="text-sm text-slt-muted mb-6">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Password') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-red-500 focus:ring-red-500"
                    placeholder="{{ __('Enter your password') }}"
                />
                @error('password', 'userDeletion')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                    class="w-full sm:w-auto text-center px-4 py-2 rounded-xl border border-white/10 text-slt-muted hover:text-white hover:bg-white/5 transition-all">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                    class="w-full sm:w-auto text-center px-4 py-2 rounded-xl bg-red-500 text-white hover:bg-red-600 transition-all">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
