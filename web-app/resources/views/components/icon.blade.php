@props(['name'])

@php
    // Small hand-built line-icon set (24x24, stroke-based) so the dashboard and
    // auth screens can use consistent, lightweight icons without pulling in a
    // new front-end dependency.
    $icons = [
        'chat' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 4v-4H6.5A2.5 2.5 0 0 1 4 12.5v-7Z" />',

        'quiz' => '<path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L8 18l-4.5 1L4.5 14.5 16.5 3.5Z" /><path d="M14.5 5.5l4 4" />',

        'chart-bar' => '<path d="M4 20V10M10 20V4M16 20v-7M4 20h16" />',

        'badge-check' => '<circle cx="12" cy="12" r="9" /><path d="M8.5 12.3l2.4 2.4 4.6-5.2" />',

        'shield-check' => '<path d="M12 3.5l7 3v5.5c0 4.5-3 7.5-7 8.5-4-1-7-4-7-8.5V6.5l7-3Z" /><path d="M9 12l2 2 4-4.5" />',

        'bell' => '<path d="M12 3.5a4 4 0 0 0-4 4V11c0 1-.4 2-1.2 2.7L5.5 15h13l-1.3-1.3c-.8-.7-1.2-1.7-1.2-2.7V7.5a4 4 0 0 0-4-4Z" /><path d="M9.5 18.5a2.5 2.5 0 0 0 5 0" />',

        'alert-triangle' => '<path d="M12 4 21 19H3L12 4Z" /><path d="M12 10.5v3.5" /><circle cx="12" cy="16.75" r="0.75" fill="currentColor" stroke="none" />',

        'check-circle' => '<circle cx="12" cy="12" r="9" /><path d="M8 12.5l2.5 2.5 5-6" />',

        'logout' => '<path d="M9 21H5.5A1.5 1.5 0 0 1 4 19.5v-15A1.5 1.5 0 0 1 5.5 3H9" /><path d="M16 16.5 21 12l-5-4.5" /><path d="M21 12H9" />',

        'sparkles' => '<path d="M12 3l1.4 4.2L17.5 8.6l-4.1 1.4L12 14.2l-1.4-4.2-4.1-1.4 4.1-1.4L12 3Z" /><path d="M19 14.5l.7 1.9 1.9.7-1.9.7-.7 1.9-.7-1.9-1.9-.7 1.9-.7.7-1.9Z" />',

        'share' => '<circle cx="6" cy="12" r="2" /><circle cx="18" cy="6" r="2" /><circle cx="18" cy="18" r="2" /><path d="M7.7 10.9l8.6-4.5" /><path d="M7.7 13.1l8.6 4.5" />',

        'clock' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3.5 2" />',

        'users' => '<circle cx="9" cy="8" r="3" /><path d="M4 19c0-2.8 2.2-5 5-5s5 2.2 5 5" /><circle cx="17" cy="9" r="2.3" /><path d="M15.5 19c.3-2.2 1.8-4 3.8-4.6" />',
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['class' => 'w-5 h-5']) }}>
    {!! $icons[$name] ?? '' !!}
</svg>
