<section>
    <header class="flex items-start sm:items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-slt-primary/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-slt-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-medium text-white">
                {{ __('Profile Information') }}
            </h2>
            <p class="text-sm text-slt-muted">
                {{ __("Update your account's profile information and email address.") }}
            </p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Name') }}</label>
            <input id="name" name="name" type="text"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Email') }}</label>
            <input id="email" name="email" type="email"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
                    <p class="text-sm text-yellow-400">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline hover:text-yellow-300">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-slt-accent">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 pt-2">
            <button type="submit" class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl bg-slt-accent text-white hover:opacity-90 transition-all">
                {{ __('Save Changes') }}
            </button>

            @if (session('status') === 'profile-updated')
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
