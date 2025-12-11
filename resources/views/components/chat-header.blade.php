@props(['title' => 'Chats', 'onSearch' => null, 'onNewChat' => null])

<div class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10 h-16">
    <h3 class="text-xl font-semibold text-gray-800">{{ $title }}</h3>
    <div class="flex items-center space-x-3">
        <!-- Search Toggle -->
        @if ($onSearch)
            <button @click="{{ $onSearch }}"
                class="text-gray-500 hover:text-gray-800 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition"
                title="Search users">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        @endif

        <!-- New Conversation Button -->
        @if ($onNewChat)
            <button @click="{{ $onNewChat }}"
                class="text-black hover:bg-gray-100 p-2 rounded-full focus:outline-none transition duration-200 ease-in-out"
                title="Start New Conversation">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z" />
                </svg>
            </button>
        @endif
    </div>
</div>
