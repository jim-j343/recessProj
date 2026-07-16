<x-guest-layout>

    {{-- Branding --}}
    <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
            <x-application-logo class="w-24 h-24" />
        </div>
        <p class="text-sm text-gray-500 mt-1">Sign in to continue to your account</p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email"
                name="email" :value="old('email')"
                placeholder="j.doe@university.ac.ug"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-xs text-gray-500 hover:text-gray-800 underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1 w-full"
                type="password" name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Remember Me --}}
        <div class="mb-6">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900"
                    name="remember">
                <span class="text-sm text-gray-600">Keep me signed in</span>
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-gray-900 text-white py-2.5 rounded-lg text-sm font-semibold
                   hover:bg-gray-700 transition-colors uppercase tracking-wide">
            Sign in
        </button>

        {{-- Divider --}}
        <div class="flex items-center gap-3 my-5">
            <div class="flex-1 h-px bg-gray-100"></div>
            <span class="text-xs text-gray-400">or</span>
            <div class="flex-1 h-px bg-gray-100"></div>
        </div>

        {{-- Register link --}}
        <div class="text-center">
            <span class="text-sm text-gray-500">New to the forum?</span>
            <a href="{{ route('register') }}"
                class="text-sm font-semibold text-gray-900 hover:underline ms-1">
                Create an account
            </a>
        </div>

    </form>

</x-guest-layout>