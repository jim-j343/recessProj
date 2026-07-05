@extends('layouts.app')

@section('content')
<div class="flex min-h-screen bg-gray-50">
    <!-- SIDEBAR -->
    <div class="w-64 bg-white border-r border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-800">Lecturer Console</h2>
        <nav class="flex flex-col gap-2 mt-4">
            <a href="#" class="bg-gray-100 text-gray-900 px-4 py-2 rounded font-medium">Configurations Wizard</a>
            <a href="#" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">Participation Scoring</a>
            <a href="{{ route('groups.create') }}" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">+ Create Group</a>
            <a href="{{ route('groups.index') }}" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">My Groups</a>
        </nav>
    </div>

    <!-- MAIN ACTION HUBS -->
    <div class="flex-1 p-8 grid grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Set Quiz Pre-Configuration</h3>
            <form class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Target Category of Students</label>
                    <input type="text" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm" placeholder="e.g. Computer Science Yr 2">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Release Date & Time</label>
                        <input type="datetime-local" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Duration (Minutes)</label>
                        <input type="number" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm" placeholder="30">
                    </div>
                </div>
                <button type="button" class="bg-gray-900 text-white w-full py-2 rounded font-semibold text-sm uppercase">Publish to Announcements</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Award Criteria Rules</h3>
            <div class="space-y-4">
                <p class="text-xs text-gray-500">Automatically scale and calculate marks for system participation metrics.</p>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-sm font-medium">Points per verified thread response</span>
                    <span class="text-sm font-bold text-indigo-600">1.5 Marks</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection