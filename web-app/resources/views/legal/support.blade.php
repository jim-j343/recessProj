<x-guest-layout>
    <div class="max-w-2xl mx-auto py-10 px-6 bg-white rounded-lg shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Support</h1>
        <div class="prose prose-sm text-gray-600 space-y-4">
            <p>Having trouble logging in, joining a group, or using the forum?
                Reach out to your group administrator first — they can reset
                memberships, review blacklist status, and fix most account
                issues directly.</p>
            <p>For platform-wide issues, contact the system administrator at
                <a href="mailto:support@smartforum.local" class="text-indigo-600 hover:underline">support@smartforum.local</a>.</p>
        </div>
        <a href="{{ route('register') }}" class="inline-block mt-6 text-sm text-indigo-600 hover:underline">← Back to registration</a>
    </div>
</x-guest-layout>