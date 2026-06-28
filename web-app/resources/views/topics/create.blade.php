<x-app-layout>

    {{-- Blurred background --}}
    <div class="fixed inset-0 bg-gray-100 z-0">
        <div class="max-w-4xl mx-auto p-8 opacity-20 blur-sm pointer-events-none">
            <div class="h-12 bg-gray-300 rounded mb-4"></div>
            <div class="h-64 bg-gray-200 rounded mb-4"></div>
            <div class="h-40 bg-gray-200 rounded"></div>
        </div>
    </div>

    {{-- Modal Overlay --}}
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-gray-200 w-full max-w-2xl shadow-xl overflow-hidden">

            {{-- Modal Header --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                <span class="text-lg">💬</span>
                <div>
                    <h2 class="font-bold text-gray-900 text-lg">New Topic</h2>
                    <p class="text-xs text-gray-400">Configure your lecture content and AI classification</p>
                </div>
                <a href="{{ route('dashboard') }}"
                    class="ml-auto text-gray-400 hover:text-gray-700 text-xl leading-none">
                    ✕
                </a>
            </div>

            <form method="POST" action="/topics/store" class="p-6 space-y-5">
                @csrf

                {{-- Title + Category --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                            Topic Title
                        </label>
                        <input type="text" name="title"
                            placeholder="e.g., The Future of Quantum Computing"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                   focus:outline-none focus:border-gray-400 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                            Category
                        </label>
                        <select name="category"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                   focus:outline-none focus:border-gray-400 transition-colors bg-white">
                            <option>General Discussion</option>
                            <option>Core Curriculum</option>
                            <option>AI & Machine Learning</option>
                            <option>Databases</option>
                            <option>Web Development</option>
                        </select>
                    </div>
                </div>

                {{-- Visibility --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                        Visibility
                    </label>
                    <select name="visibility"
                        class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                               focus:outline-none focus:border-gray-400 transition-colors bg-white">
                        <option>All Students 👁</option>
                        <option>Year 1 Only</option>
                        <option>Year 2 Only</option>
                        <option>Year 3 Only</option>
                    </select>
                </div>

                {{-- Description with toolbar --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                        Description
                    </label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-1 px-3 py-2 border-b border-gray-100 bg-gray-50">
                            @foreach(['B', 'I', '≡', '⋮', '🔗', '🖼', '</>'] as $tool)
                            <button type="button"
                                class="w-8 h-8 flex items-center justify-center text-gray-500
                                       hover:bg-gray-200 rounded text-xs font-semibold transition-colors">
                                {{ $tool }}
                            </button>
                            @endforeach
                        </div>
                        <textarea name="description" rows="5"
                            placeholder="Write your discussion prompt here..."
                            class="w-full px-4 py-3 text-sm focus:outline-none resize-none">
                        </textarea>
                    </div>
                </div>

                {{-- AI Tag Suggestions --}}
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-3">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide flex items-center gap-1">
                            ✦ AI Tag Suggestions
                        </p>
                        <button type="button" class="text-xs text-blue-600 font-semibold hover:underline">
                            Refresh
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['#ComputerScience', '#EthicsInAI', '#FutureTech'] as $tag)
                        <span class="text-xs bg-white border border-blue-200 text-blue-700 px-3 py-1 rounded-full cursor-pointer hover:bg-blue-100 transition-colors">
                            {{ $tag }}
                        </span>
                        @endforeach
                        <button type="button"
                            class="text-xs text-blue-500 hover:underline">
                            + Add tag
                        </button>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button type="button"
                        class="flex-1 border border-gray-200 text-gray-700 py-3 rounded-lg text-sm
                               font-semibold hover:bg-gray-50 transition-colors">
                        Save Draft
                    </button>
                    <button type="submit"
                        class="flex-1 bg-gray-900 text-white py-3 rounded-lg text-sm font-semibold
                               flex items-center justify-center gap-2 hover:bg-gray-700 transition-colors">
                        ▶ Publish Topic
                    </button>
                </div>

            </form>
        </div>
    </div>

</x-app-layout>