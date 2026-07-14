<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Edit Reply
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto">

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('posts.update', $post) }}" method="POST">
                @csrf
                @method('PUT')

                <textarea
                    name="content"
                    rows="8"
                    class="w-full border rounded-lg p-4"
                    required>{{ old('content', $post->content) }}</textarea>

                <div class="mt-4 flex gap-3">

                    <a href="{{ route('topics.show', $post->topic_id) }}"
                       class="px-4 py-2 bg-gray-300 rounded">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded">
                        Update Reply
                    </button>

                </div>

            </form>

        </div>
    </div>
</x-app-layout>