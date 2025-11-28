<div x-data="notificationComponent" class="relative z-50">
    <button @click="open = !open" @click.outside="open = false"
        class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <span x-show="count > 0" x-text="count" x-transition.scale
            class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full"></span>
    </button>

    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 w-80 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        style="display: none;">
        <div class="py-1">
            <div class="px-4 py-2 text-sm font-semibold text-gray-700 border-b border-gray-100">
                Notifications
            </div>

            <div x-show="count === 0" class="px-4 py-6 text-sm text-center text-gray-500">
                No new notifications.
            </div>

            <ul class="max-h-[24rem] overflow-y-auto divide-y divide-gray-100">
                <template x-for="item in notifications" :key="item.id">
                    <li class="relative group hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start p-4">

                            <div class="flex-shrink-0 mt-1">
                                <span
                                    class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </span>
                            </div>

                            <div class="ml-3 flex-1 w-0">
                                <div class="mt-2">
                                    <a href="{{ route('posts.index') }}"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-500 hover:underline">
                                        <p class="text-sm font-semibold text-gray-900" x-text="item.data.title"></p>
                                    </a>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2" x-text="item.data.message"></p>

                                <p class="mt-1 text-xs text-gray-400" x-text="formatDate(item.created_at)"></p>
                            </div>

                            <button @click.stop="remove(item.id)"
                                class="ml-2 -mr-1 text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100"
                                title="Mark as read">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>

            {{-- <div x-show="count > 0"
                class="block px-4 py-2 text-xs text-center text-gray-500 bg-gray-50 hover:bg-gray-100 cursor-pointer">
                View all notifications
            </div> --}}
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('notificationComponent', () => ({
            open: false,
            notifications: @js(auth()->user()->unreadNotifications),
            userId: "{{ auth()->id() }}",

            init() {
                // console.log(this.notifications);
                this.$nextTick(() => {
                    if (window.Echo) {
                        window.Echo.private(`notification.${this.userId}`)
                            .notification((response) => {
                                console.log(response);

                                this.notifications.push(response)
                            });
                    } else {
                        console.log('notification channel not available.');
                    }
                });
            },
        
            // getter count
            get count() {
                return this.notifications.length;
            },

            remove(id) {
                // 1. Optimistic UI update: Remove from list immediately
                this.notifications = this.notifications.filter(item => item.id !== id);

                axios.delete(`/notifications/${id}`)
                    .catch(error => {
                        console.error('Message failed:', error);
                        alert(error.response.data.message);
                    });
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }));
    });
</script>
