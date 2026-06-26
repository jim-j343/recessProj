<x-guest-layout>

    {{-- App Branding --}}
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Smart Discussion Forum</h1>
        <p class="text-sm text-gray-500 mt-1">Create your account to get started.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text"
                name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email"
                name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Role Selection --}}
        <div class="mt-4">
            <x-input-label for="role" :value="__('I am a...')" />
            <select id="role" name="role" required
                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                       focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="" disabled selected>Select your role</option>
                <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                <option value="lecturer" {{ old('role') == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        {{-- Student Number (shown only for students) --}}
        <div class="mt-4" id="student-number-field">
            <x-input-label for="student_number" :value="__('Student Number')" />
            <x-text-input id="student_number" class="block mt-1 w-full" type="text"
                name="student_number" :value="old('student_number')"
                placeholder="e.g. 2300700123" />
            <x-input-error :messages="$errors->get('student_number')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password" name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Confirm Password --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password" name="password_confirmation"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- Terms of Service (FIXED position) --}}
        <div class="mt-4">
            <label class="flex items-center">
                <input type="checkbox" name="rules_accepted" required
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600">
                    I have read and agree to the
                    <a href="/rules" class="text-indigo-600 underline hover:text-indigo-800">
                        Platform Rules
                    </a>.
                </span>
            </label>
        </div>

        {{-- Already registered + Submit --}}
        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900"
                href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>

    </form>

    {{-- Show/hide Student Number based on role --}}
    <script>
        const roleSelect = document.getElementById('role');
        const studentNumberField = document.getElementById('student-number-field');

        // Hide by default
        studentNumberField.style.display = 'none';

        roleSelect.addEventListener('change', function () {
            if (this.value === 'student') {
                studentNumberField.style.display = 'block';
            } else {
                studentNumberField.style.display = 'none';
            }
        });
    </script>

</x-guest-layout>