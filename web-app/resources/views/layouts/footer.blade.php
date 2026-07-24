<footer class="bg-white border-t border-gray-200 shadow-inner mt-auto">

    <div class="max-w-7xl mx-auto px-6 py-5">

        <div class="flex flex-col lg:flex-row justify-between items-center gap-6">

            <!-- Brand -->
            <div class="text-center lg:text-left">

                <div class="flex items-center justify-center lg:justify-start gap-2">

                    <x-application-logo class="w-8 h-8 rounded" />

                    <span class="text-lg font-bold text-indigo-700">
                        ACES
                    </span>

                </div>

                <p class="text-sm text-gray-500 mt-2">
                    Academic Collaboration and Evaluation system
                </p>

            </div>

            <!-- Footer Links -->
            <div class="flex flex-wrap justify-center gap-8">

                <a href="{{ route('privacy') }}"
                   class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition duration-200">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"/>
                    </svg>

                    <span>Privacy Policy</span>

                </a>

                <button
                    type="button"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal','rules')"
                    class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition duration-200">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 12h6m-6 4h6M8 4h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                    </svg>

                    <span>Platform Rules</span>

                </button>

                <a href="{{ route('support') }}"
                   class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition duration-200">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M8 10h8M8 14h5m8-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>

                    <span>Support</span>

                </a>

            </div>

        </div>



    </div>

</footer>