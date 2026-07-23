<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Group Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                @if($errors->any())
                    <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('groups.update', $group->group_id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Group Name *</label>
                        <input type="text" name="name" value="{{ old('name', $group->name) }}" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course / Unit *</label>
                        <input type="text" name="course_name" value="{{ old('course_name', $group->course_name) }}" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        <p class="text-xs text-gray-400 mt-1">Lets students see which course this group is for before joining.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <textarea name="description" rows="3"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $group->description) }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Days before warning *</label>
                            <input type="number" name="inactivity_warning_days"
                                value="{{ old('inactivity_warning_days', $group->inactivity_warning_days) }}" min="1" required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-xs text-gray-400 mt-1">Days of inactivity before warning</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blacklist duration (days) *</label>
                            <input type="number" name="blacklist_duration_days"
                                value="{{ old('blacklist_duration_days', $group->blacklist_duration_days) }}" min="1" required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-xs text-gray-400 mt-1">How long blacklisted members are banned</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('groups.show', $group->group_id) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
