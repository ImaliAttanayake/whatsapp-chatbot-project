<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-xl font-semibold text-white mb-6 text-center">Sign in to your account</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   required autofocus autocomplete="username" />
            @error('email')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slt-muted mb-2">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary"
                   required autocomplete="current-password" />
            @error('password')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-white/5 border-white/10 text-slt-primary focus:ring-slt-primary" name="remember">
                <span class="ms-2 text-sm text-slt-muted">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-slt-info hover:text-slt-info/80" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="w-full py-3 rounded-xl bg-slt-accent text-white font-medium hover:opacity-90 transition-all">
            {{ __('Log in') }}
        </button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slt-muted">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-slt-info hover:text-slt-info/80">Sign up</a>
            </p>
        @endif

        <p class="text-center text-sm text-slt-muted">
            <a href="{{ route('superadmin.login') }}" class="text-slt-info hover:text-slt-info/80">Super Admin Login</a>
        </p>
    </form>
</x-guest-layout>
