<x-guest-layout>

    {{-- Branding --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-900 rounded-xl mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Smart Discussion Forum</h1>
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