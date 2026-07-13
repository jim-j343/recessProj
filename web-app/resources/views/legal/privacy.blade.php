<x-guest-layout>
    <div class="max-w-2xl mx-auto py-10 px-6 bg-white rounded-lg shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
        <div class="prose prose-sm text-gray-600 space-y-4">
            <p>Smart Discussion Forum collects only the information needed to run
                the platform: your name, email, group memberships, posts, and
                participation records used for grading and moderation.</p>
            <p>Your data is visible to group administrators and lecturers for the
                groups you belong to, and is never shared outside the platform.</p>
            <p>You can request an export or deletion of your account data by
                contacting your group administrator.</p>
        </div>
        <a href="{{ route('register') }}" class="inline-block mt-6 text-sm text-indigo-600 hover:underline">← Back to registration</a>
    </div>
</x-guest-layout>