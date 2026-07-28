@props(['name' => '', 'size' => 'w-8 h-8', 'user' => null])

@php
    $trimmed = trim($name);
    $initial = $trimmed !== '' ? mb_strtoupper(mb_substr($trimmed, 0, 1)) : '?';
    $avatarPath = $user?->avatar;
@endphp

@if($avatarPath)
    <img src="{{ asset('storage/'.$avatarPath) }}" alt="{{ $trimmed }}"
         {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover bg-gray-100"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size shrink-0 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-xs leading-none"]) }}>
        {{ $initial }}
    </div>
@endif
