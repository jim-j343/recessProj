{{-- PLATFORM RULES / TERMS OF SERVICE MODAL --}}
    <x-modal name="rules" maxWidth="2xl">
        <div class="px-8 py-5 borders-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Platform Rules and Terms of Service</h3>
            <p class="text-xs text-gray-400 mt-1">Version 1.0.0 · Last Updated June 2026</p>
        </div>

        <div class="max-h-[60vh] overflow-y-auto px-8 py-6 space-y-6 text-sm text-gray-600">

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
                <h4 class="font-semibold text-gray-800 mb-2">2. Data Privacy &amp; Processing</h4>
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

        <div class="px-8 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
            <p class="text-xs text-gray-500">
                Please read all terms before checking the box on the registration form.
            </p>
            <button type="button" x-on:click="$dispatch('close')"
                class="bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-gray-700">
                Close
            </button>
        </div>
    </x-modal>