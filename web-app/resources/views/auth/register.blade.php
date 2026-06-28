<x-guest-layout>
    <div class="min-h-screen flex flex-col">

        {{-- TOP NAR --}}
        <div class="flex justify-between items-center px-8 py-4 border-b border-gray-100">
            <h1 class="text-lg font-bold text-gray-800">Smart Discussion Forum</h1>
            <div class="flex gap-3">
                <a href="/" class="text-gray-500 hover:text-gray-700 text-sm">?</a>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="flex flex-1">

            {{-- LEFT: Form --}}
            <div class="w-1/2 px-16 py-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-1">Initialize Your Account</h2>
                <p class="text-sm text-gray-500 mb-8">
                    Complete the onboarding to gain access to the Smart Discussion Forum.
                </p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    {{-- Full Name --}}
                    <div class="mb-5">
                        <x-input-label for="name" :value="__('Full Legal Name')" />
                        <x-text-input id="name" name="name" type="text"
                            class="block mt-1 w-full"
                            placeholder="Johnathan Doe"
                            :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Email --}}
                    <div class="mb-5">
                        <x-input-label for="email" :value="__('Email Address')" />
                        <x-text-input id="email" name="email" type="email"
                            class="block mt-1 w-full"
                            placeholder="j.doe@university.ac.ug"
                            :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Role --}}
                    <div class="mb-5">
                        <x-input-label for="role" :value="__('I am a...')" />
                        <select id="role" name="role" required
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="" disabled selected>Select your role</option>
                            <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>
                                Student
                            </option>
                            <option value="lecturer" {{ old('role') == 'lecturer' ? 'selected' : '' }}>
                                Lecturer
                            </option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    {{-- Student Number --}}
                    <div class="mb-5 hidden" id="student-number-field">
                        <x-input-label for="student_number" :value="__('Student Number')" />
                        <x-text-input id="student_number" name="student_number" type="text"
                            class="block mt-1 w-full"
                            placeholder="e.g. 2300700123"
                            :value="old('student_number')" />
                        <x-input-error :messages="$errors->get('student_number')" class="mt-2" />
                    </div>

                    {{-- Password --}}
                    <div class="mb-5">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" name="password" type="password"
                            class="block mt-1 w-full"
                            required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-5">
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation"
                            type="password" class="block mt-1 w-full"
                            required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    {{-- Terms checkbox + Submit --}}
                    <div class="flex items-center justify-between mt-6">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="rules_accepted" required
                                class="rounded border-gray-300 text-indigo-600
                                       shadow-sm focus:ring-indigo-500" />
                            <span class="text-sm text-gray-600">
                                I agree to the
                                <a href="/rules" class="text-indigo-600 underline hover:text-indigo-800">
                                    Terms of Service
                                </a>
                                and Platform Rules.
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <a href="{{ route('login') }}"
                            class="text-sm text-gray-500 hover:text-gray-700 underline">
                            Already registered?
                        </a>
                        <button type="submit"
                            class="bg-gray-900 text-white px-6 py-2 rounded-md text-sm
                                   font-semibold hover:bg-gray-700 uppercase tracking-wide">
                            Register Account
                        </button>
                    </div>

                </form>
            </div>

            {{-- RIGHT: Terms of Service --}}
            <div class="w-1/2 border-l border-gray-200 flex flex-col">
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Platform Rules and Terms of Service</h3>
                    <p class="text-xs text-gray-400 mt-1">Version 1.0.0 · Last Updated June 2026</p>
                </div>

                {{-- Scrollable ToS content --}}
                <div class="flex-1 overflow-y-auto px-8 py-6 space-y-6 text-sm text-gray-600"
                     style="max-height: calc(100vh - 200px)">

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">1. Acceptable Use Policy</h4>
                        <p class="mb-2">Users of the Smart Discussion Forum are strictly prohibited
                            from utilizing the system for any purpose other than authorized academic
                            discussion and learning.</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-500">
                            <li>All identity information provided must be verifiable through
                                official university records.</li>
                            <li>Automated scripts or bot interactions are strictly prohibited.</li>
                            <li>Users are responsible for maintaining the confidentiality
                                of their credentials.</li>
                            <li>Data scraping of internal content is a violation of this policy.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">2. Data Privacy & Processing</h4>
                        <p class="mb-2">The Smart Discussion Forum processes submitted data under
                            applicable data protection frameworks. Your identification and email
                            are encrypted at rest.</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-500">
                            <li>Data is stored for the duration of your academic tenure.</li>
                            <li>Personal data is never shared with third-party vendors.</li>
                            <li>System logs include IP address and interaction timestamps
                                for security auditing.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">3. Academic Integrity</h4>
                        <p class="mb-2">All forum contributions must represent the student's own
                            thoughts and ideas.</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-500">
                            <li>Plagiarism or copying of other students' posts is prohibited.</li>
                            <li>Respectful and constructive engagement is required at all times.</li>
                            <li>Offensive, abusive, or discriminatory content will result in
                                immediate account suspension.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">4. Quiz Conduct</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-500">
                            <li>Quiz sessions are timed and monitored.</li>
                            <li>Attempting to share quiz questions or answers is strictly
                                prohibited.</li>
                            <li>Any form of cheating will result in a zero grade and
                                disciplinary action.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">5. Account Termination</h4>
                        <p class="text-gray-500">The administration reserves the right to suspend
                            or permanently remove any account that violates these terms without
                            prior notice.</p>
                    </div>

                </div>

                {{-- Bottom checkbox reminder --}}
                <div class="px-8 py-4 border-t border-gray-200 bg-gray-50 flex items-center
                            justify-between">
                    <p class="text-xs text-gray-500">
                        Please read all terms before registering.
                    </p>
                </div>
            </div>

        </div>

        {{-- FOOTER --}}
        <div class="border-t border-gray-100 px-8 py-4 flex justify-between text-xs text-gray-400">
            <span>Smart Discussion Forum</span>
            <div class="flex gap-4">
                <a href="#" class="hover:text-gray-600">Privacy Policy</a>
                <a href="#" class="hover:text-gray-600">Platform Rules</a>
                <a href="#" class="hover:text-gray-600">Support</a>
            </div>
            <span>© 2026 Smart Discussion Forum. All rights reserved.</span>
        </div>

    </div>

    {{-- Show/hide Student Number --}}
    <script>
        const roleSelect = document.getElementById('role');
        const studentField = document.getElementById('student-number-field');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'student') {
                studentField.classList.remove('hidden');
            } else {
                studentField.classList.add('hidden');
            }
        });
    </script>
</x-guest-layout>