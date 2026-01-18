<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Masuk ke Sistem</h1>
        <p class="text-sm text-gray-500">Gunakan akun yang sudah terdaftar.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-xs text-gray-600" />
            <x-text-input id="email"
                class="block mt-1 w-full rounded-lg border-gray-200 focus:border-emerald-500 focus:ring-emerald-500"
                type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-xs text-gray-600" />
            <x-text-input id="password"
                class="block mt-1 w-full rounded-lg border-gray-200 focus:border-emerald-500 focus:ring-emerald-500"
                type="password"
                name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" name="remember">
                {{ __('Remember me') }}
            </label>

            @if (Route::has('password.request'))
            <a class="text-sm text-emerald-700 hover:text-emerald-800" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
            @endif
        </div>

        <button type="submit"
            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition">
            {{ __('Log in') }}
        </button>
    </form>
</x-guest-layout>
