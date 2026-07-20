<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Topic
            </h2>
            <a href="{{ route('forum.index') }}"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200">
                ← Back to Forum
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
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('topics.store') }}">
                    @csrf

                    {{-- Title --}}
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Topic Title
                        </label>
                        <input type="text" name="title" id="title"
                            value="{{ old('title') }}" required
                            placeholder="Enter a clear, descriptive title..."
                            class="w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    {{-- Category --}}
                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-gray-400">(optional)</span>
                        </label>
                        <select name="category" id="category"
                            class="w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Select a category --</option>
                            @foreach([
                                'SDLC', 'Methodology', 'Requirements', 'Testing', 'Best Practices',
                                'Tools', 'Web Development', 'Networking', 'Programming',
                                'Architecture', 'Database', 'Numerical Analysis',
                            ] as $cat)
                                <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Group --}}
                    <div class="mb-4">
                        <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Group <span class="text-red-500">*</span>
                        </label>
                        @if($groups->isEmpty())
                            <p class="text-sm text-red-500">
                                You have not joined any groups yet.
                                <a href="{{ route('groups.index') }}" class="underline">Join a group first</a>.
                            </p>
                        @else
                            <select name="group_id" id="group_id" required
                                class="w-full border-gray-300 rounded-md shadow-sm
                                       focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Select a group --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->group_id }}"
                                        {{ old('group_id') == $group->group_id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea name="content" id="content" rows="8" required
                            placeholder="Describe your topic in detail..."
                            class="w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500">{{ old('content') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('forum.index') }}"
                            class="text-gray-600 hover:text-gray-900 text-sm mr-4">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-md text-sm
                                   font-medium hover:bg-indigo-700 transition">
                            Post Topic
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
