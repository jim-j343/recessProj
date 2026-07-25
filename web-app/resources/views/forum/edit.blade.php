<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Topic
            </h2>
            <a href="{{ route('topics.show', $topic) }}"
               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200">
                ← Back to Topic
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm rounded-lg p-6">

                @if(session('success'))
                    <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc ml-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('topics.update', $topic) }}">
                    @csrf
                    @method('PUT')

                    {{-- Topic Title --}}
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Topic Title
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            required
                            value="{{ old('title', $topic->title) }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Category --}}
                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Category
                        </label>
                        <select
                            id="category"
                            name="category"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                            <option value="">-- Select a category --</option>

                            @foreach([
                                'SDLC', 'Methodology', 'Requirements', 'Testing',
                                'Tools', 'Web Development', 'Networking', 'Programming',
                                'Architecture', 'Database', 'Numerical Analysis',
                            ] as $cat)
                                <option value="{{ $cat }}"
                                    {{ old('category', $topic->category) == $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Group --}}
                    <div class="mb-4">
                        <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Group
                        </label>
                        <select
                            id="group_id"
                            name="group_id"
                            required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                            @foreach($groups as $group)
                                <option value="{{ $group->group_id }}"
                                    {{ old('group_id', $topic->group_id) == $group->group_id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Topic Content --}}
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            id="content"
                            name="content"
                            rows="8"
                            required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('content', $firstPost?->content) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('topics.show', $topic) }}"
                           class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-md">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md">
                            Update Topic
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
