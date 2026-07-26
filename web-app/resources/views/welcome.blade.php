<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ACES') }} — Smart Discussion Forum</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @unless(app()->runningUnitTests())
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endunless
    </head>
    <body class="font-sans text-gray-900 antialiased bg-white">

        {{-- ═══════════════════ NAVBAR ═══════════════════ --}}
        <header class="border-b border-gray-100 sticky top-0 bg-white/90 backdrop-blur z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-auto" />
                    <span class="font-bold text-lg tracking-tight text-gray-900">ACES</span>
                </a>

                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 transition-colors">
                            Log in
                        </a>
                        <a href="{{ route('register') }}"
                           class="bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                            Create account
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        {{-- ═══════════════════ HERO ═══════════════════ --}}
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-indigo-50/60 to-white pointer-events-none"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
                <div class="inline-flex items-center gap-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                    <x-icon name="sparkles" class="w-3.5 h-3.5" />
                    Built for course-based collaboration
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 leading-tight">
                    The discussion forum built<br class="hidden sm:block"> for your classroom.
                </h1>
                <p class="mt-5 text-lg text-gray-500 max-w-2xl mx-auto">
                    ACES brings course discussions, group collaboration, quizzes, and participation
                    tracking into one place — so students and lecturers spend less time coordinating
                    and more time learning.
                </p>
                <div class="mt-8 flex items-center justify-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="bg-gray-900 text-white px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="bg-gray-900 text-white px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                            Get started free
                        </a>
                        <a href="{{ route('login') }}"
                           class="border border-gray-300 text-gray-700 px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-50 transition-colors">
                            Sign in
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- ═══════════════════ FEATURES ═══════════════════ --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Everything your course needs, in one forum</h2>
                <p class="mt-3 text-gray-500 max-w-xl mx-auto">
                    Purpose-built for academic discussion — not a generic chat app repurposed for class.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="chat" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Course discussions</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Start topics, reply to classmates, and keep every conversation organized by course and group.
                    </p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="users" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Groups that mirror your class</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Join or get assigned to course groups, so discussions stay relevant to the people actually taking the class with you.
                    </p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="quiz" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Live & scheduled quizzes</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Lecturers publish timed quizzes directly in the forum; results and grading flow straight into student progress.
                    </p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="chart-bar" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Real participation tracking</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        See forum activity, quiz scores, and how you compare to peers — all backed by real engagement data, not guesswork.
                    </p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="badge-check" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Lecturer feedback built in</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Lecturers grade participation and leave remarks directly on student progress pages — no separate spreadsheet needed.
                    </p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                        <x-icon name="shield-check" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Moderation & admin controls</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Flagging, post moderation, and analytics keep the forum on-topic and give admins visibility across the board.
                    </p>
                </div>
            </div>
        </section>

        {{-- ═══════════════════ ROLE HIGHLIGHT ═══════════════════ --}}
        <section class="bg-gray-50 border-y border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">For students</div>
                    <h3 class="font-semibold text-gray-900 text-lg">Stay on top of every course</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        One dashboard for quizzes due, discussions to catch up on, and how your participation stacks up against the class average.
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">For lecturers</div>
                    <h3 class="font-semibold text-gray-900 text-lg">Grade and guide, without the overhead</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Publish quizzes, grade participation, and leave remarks — all inside the same forum students are already using.
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">For admins</div>
                    <h3 class="font-semibold text-gray-900 text-lg">Full visibility, less manual moderation</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Analytics, member management, and moderation tools in one console — built for keeping a growing forum healthy.
                    </p>
                </div>
            </div>
        </section>

        {{-- ═══════════════════ CTA ═══════════════════ --}}
        <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Ready to bring your class discussions together?</h2>
            <p class="mt-3 text-gray-500 max-w-xl mx-auto">
                Sign in with your university account, or create one in under a minute.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="bg-gray-900 text-white px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="bg-gray-900 text-white px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-700 transition-colors">
                        Create your account
                    </a>
                    <a href="{{ route('login') }}"
                       class="border border-gray-300 text-gray-700 px-6 py-3 rounded-md text-sm font-semibold hover:bg-gray-50 transition-colors">
                        Sign in
                    </a>
                @endauth
            </div>
        </section>

        {{-- ═══════════════════ FOOTER ═══════════════════ --}}
        @include('layouts.footer')

    </body>
</html>