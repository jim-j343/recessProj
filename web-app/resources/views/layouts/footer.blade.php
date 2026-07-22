<footer class="bg-white border-t border-gray-200 shadow-inner mt-auto">

    <div class="max-w-7xl mx-auto px-6 py-6">

        <div class="flex flex-col md:flex-row justify-between items-center gap-5">

            <!-- Left -->
            <div class="text-center md:text-left">
                <h3 class="font-bold text-lg text-indigo-700">
                    Smart Discussion Forum
                </h3>

                <p class="text-sm text-gray-500">
                    Empowering collaborative learning through meaningful discussions.
                </p>
            </div>

            <!-- Center -->
            <div class="flex flex-wrap justify-center gap-6 text-sm">

                <a href="{{ route('privacy') }}"
                   class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition">

                    🛡
                    <span>Privacy Policy</span>

                </a>

                <button
                    type="button"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal','rules')"
                    class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition">

                    📜
                    <span>Platform Rules</span>

                </button>

                <a href="{{ route('support') }}"
                   class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition">

                    💬
                    <span>Support</span>

                </a>

            </div>

        </div>



    </div>

    {{-- KEEP YOUR ORIGINAL PLATFORM RULES MODAL BELOW THIS --}}
</footer>