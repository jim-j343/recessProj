<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">✏️ Create New Topic</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="/topics/store">
                @csrf

                <div class="bg-white rounded-lg shadow p-6 space-y-5">

                    {{-- Topic Title --}}
                    <div>
                        <x-input-label for="title" :value="__('Topic Title')" />
                        <x-text-input id="title" name="title" type="text"
                            class="block mt-1 w-full"
                            placeholder="e.g. Introduction to Machine Learning"
                            :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    {{-- Category --}}
                    <div>
                        <x-input-label for="category" :value="__('Category')" />
                        <select id="category" name="category" required
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="" disabled selected>Select a category</option>
                            <option value="ai" {{ old('category') == 'ai' ? 'selected' : '' }}>
                                Artificial Intelligence
                            </option>
                            <option value="databases" {{ old('category') == 'databases' ? 'selected' : '' }}>
                                Databases
                            </option>
                            <option value="web" {{ old('category') == 'web' ? 'selected' : '' }}>
                                Web Development
                            </option>
                            <option value="networking" {{ old('category') == 'networking' ? 'selected' : '' }}>
                                Networking
                            </option>
                            <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>
                                General
                            </option>
                        </select>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    {{-- Topic Description --}}
                    <div>
                        <x-input-label for="description" :value="__('Description / Opening Post')" />
                        <textarea id="description" name="description" rows="6" required
                            placeholder="Write the opening post for this topic..."
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    {{-- Visibility --}}
                    <div>
                        <x-input-label :value="__('Visible To')" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="visibility" value="all"
                                    {{ old('visibility', 'all') == 'all' ? 'checked' : '' }}
                                    class="accent-indigo-600" />
                                <span class="text-sm text-gray-700">All Students</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="visibility" value="year1"
                                    {{ old('visibility') == 'year1' ? 'checked' : '' }}
                                    class="accent-indigo-600" />
                                <span class="text-sm text-gray-700">Year 1 Only</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="visibility" value="year2"
                                    {{ old('visibility') == 'year2' ? 'checked' : '' }}
                                    class="accent-indigo-600" />
                                <span class="text-sm text-gray-700">Year 2 Only</span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                    </div>

                    {{-- Pin Topic option --}}
                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_pinned" value="1"
                                {{ old('is_pinned') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600
                                       shadow-sm focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">
                                📌 Pin this topic to the top of the dashboard
                            </span>
                        </label>
                    </div>

                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-3 mt-4">
                    <a href="{{ route('dashboard') }}"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300
                               rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg
                               text-sm font-semibold hover:bg-indigo-700">
                        Publish Topic
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>