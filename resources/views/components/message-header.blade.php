<div class="p-4 border-b bg-white flex items-center justify-between">
    <h3 class="text-lg font-semibold" x-text="users.find(u => u.id === selectedUserId)?.name || 'Loading...'"></h3>

    <button @click="$store.chatSelection.toggle()" 
        class="p-2 rounded-full shadow-md border border-gray-200 transition"
        :class="$store.chatSelection.active ? 'bg-indigo-50 text-indigo-600 ring-2 ring-indigo-500' :
            'bg-white text-gray-600 hover:text-indigo-600'"
        title="Select Messages">

        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
</div>
