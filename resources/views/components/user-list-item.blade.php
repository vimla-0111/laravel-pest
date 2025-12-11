<button @click="selectUser(user.id)"
    class="w-full text-left p-4 flex items-center hover:bg-indigo-50 focus:outline-none border-b border-gray-100 transition duration-150 ease-in-out"
    :class="{
        'bg-indigo-100': selectedUserId === user.id,
        'cursor-default opacity-50': user.id === currentUserId
    }">

    <!-- User Avatar -->
    <div class="relative mr-3">
        <div class="w-10 h-10 bg-gray-400 rounded-full flex-shrink-0 overflow-hidden">
            <img :src="user.avatar || 'https://ui-avatars.com/api/?name=' + user.name" 
                alt="" 
                class="w-full h-full object-cover">
        </div>
        
        <span x-show="activeUserIds.includes(user.id)"
            x-transition.scale.origin.center
            class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-green-500">
        </span>
    </div>

    <!-- User Info -->
    <div class="flex-1 min-w-0">
        <div class="flex justify-between items-center mb-1">
            <span x-text="user.name" class="font-semibold text-gray-900 truncate"></span>
        
            <span x-show="user.unread_message_count > 0" x-text="user.unread_message_count"
                class="ml-2 flex items-center justify-center bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[1.25rem]">
            </span>
        </div>

        <p x-text="user?.last_message || 'No messages yet'"
            class="text-sm text-gray-500 truncate"
            :class="{ 'text-indigo-700 font-medium': user.unread_message_count > 0 }">
        </p>
    </div>
</button>
