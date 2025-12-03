<x-app-layout>
    <x-slot name="style">
        {{-- @vite('resources/js/echo.js')       // do it when include js in this page only --}}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Global Chat Room') }}
        </h2>
    </x-slot>

    <div x-data="mainComponent" class="shadow-xl sm:rounded-lg flex h-[80vh] overflow-hidden">
        <div class="w-1/4 border-r border-gray-200  h-screen overflow-y-auto flex flex-col" x-data="searchComponent">


            <div class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10 h-16">
                <h3 class="text-xl font-semibold text-gray-800">Chats</h3>
                <div class="flex items-center space-x-3">
                    <button @click="toggleSearch()"
                        class="text-gray-500 hover:text-gray-800 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition"
                        title="Search users">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button @click="showNewChatModal = true"
                        class="text-gray-500 hover:text-gray-800 hover:bg-gray-100 p-2 rounded-full focus:outline-none transition duration-200 ease-in-out"
                        title="Start New Conversation">

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z" />
                        </svg>
                    </button>
                </div>

                {{-- <button @click="toggleearch()"
                    class="text-gray-500 hovSer:text-gray-800 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition"
                    title="Search users">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button> --}}
            </div>

            <div x-show="showSearch" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="p-2 border-b bg-gray-50" style="display: none;">
                <div class="relative">
                    <input x-ref="searchInput" type="text" x-model.debounce.1000ms="search"
                        placeholder="Find a user..."
                        class="w-full pl-3 pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <button x-show="search.length > 0" @click="search = ''"
                        class="absolute right-2 top-2.5 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="divide-y divide-gray-100 flex-1 overflow-y-auto">
                {{-- <h3 class="text-xl font-semibold p-4 border-b">Chats</h3> --}}
                <template x-for="user in filteredUsers" :key="user.id">
                    <button @click="selectUser(user.id)"
                        class="w-full text-left p-4 flex items-center hover:bg-indigo-50 focus:outline-none border-b border-gray-100 transition duration-150 ease-in-out"
                        :class="{
                            'bg-indigo-100': selectedUserId === user.id,
                            'cursor-default opacity-50': user.id === currentUserId
                        }">

                        <div class="relative mr-3">
                            <div class="w-10 h-10 bg-gray-400 rounded-full flex-shrink-0 overflow-hidden">
                                <img :src="user.avatar ||
                                    'https://ui-avatars.com/api/?name=' + user.name" alt="" class="w-full h-full object-cover">
                        </div>

                        <span x-show="activeUserIds.includes(user.id)"
                            x-transition.scale.origin.center
                            class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-green-500">
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span x-text="user.name" class="font-semibold text-gray-900 truncate"></span>
                        
                            <span x-show="user.unread_message_count > 0"  x-text="user.unread_message_count"
                                class="ml-2 flex items-center justify-center bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[1.25rem]">
                            </span>
                        </div>

                        <p x-text="user?.last_message || 'No messages yet '"
                                    class="text-sm text-gray-500 truncate"
                                    :class="{ 'text-indigo-700 font-medium': user.unread_message_count > 0 }">
                                </p>
                            </div>
                    </button>
                </template>
                <template x-if="filteredUsers.length === 0">
                    <div class="p-8 text-center text-gray-500 text-sm">
                        <span x-show="search !== ''">No users found matching "<span x-text="search"
                                class="font-semibold"></span>"</span>
                        <span x-show="search === ''">No active chats.</span>
                    </div>
                </template>
            </div>

            <x-new-chat-modal x-bind-show-new-chat-modal="showNewChatModal" />
        </div>

        <div class="w-3/4 flex flex-col">
            <template x-if="selectedUserId">
                <div class="flex flex-col h-full">
                    <div class="p-4 border-b bg-white flex items-center">
                        <h3 class="text-lg font-semibold"
                            x-text="users.find(u => u.id === selectedUserId)?.name || 'Loading...'">
                        </h3>
                    </div>

                    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <template x-if="conversationId">
                            <div class="h-full w-full">
                                <x-chat :conversationId="null" x-bind:conversation-id="conversationId"
                                    x-bind:current-user-id="currentUserId" x-bind:selected-user-id="selectedUserId" />
                            </div>
                        </template>

                        <template x-if="isLoading">
                            <div class="h-full flex items-center justify-center text-gray-500">
                                Loading...
                            </div>
                        </template>
                    </div>

                    <template x-if="isLoading">
                        <div class="flex-grow flex items-center justify-center text-gray-500">
                            <svg class="animate-spin h-5 w-5 mr-3 text-indigo-500" viewBox="0 0 24 24">...</svg>
                            <span>Loading chat...</span>
                        </div>
                    </template>
                    <template x-if="!selectedUserId && !isLoading">
                        <div class="flex-grow flex items-center justify-center text-gray-400">
                            Select a user to start chatting.
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!selectedUserId">
                <div class="flex-grow flex items-center justify-center text-gray-400">
                    Select a user to start chatting.
                </div>
            </template>
        </div>
    </div>
    <x-slot name="scripts">
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('mainComponent', () => ({
                    // Array of all users available for chat
                    users: @js($users),
                    // ID of the user currently selected in the sidebar
                    selectedUserId: null,
                    // The ID of the currently authenticated user (for determining message direction)
                    currentUserId: {{ auth()->user()->id }},
                    // Holds the conversation ID once selected or created (initially null)
                    conversationId: null,
                    // Loading state
                    isLoading: false,
                    activeUserIds: [], // Stores IDs of online users

                    init() {
                        // Listen for event ONLY inside parent component
                        this.$root.addEventListener('set-lastMessage', (e) => {
                            console.log('new message received:', e.detail.message);

                            this.users = this.users.map(user => {
                                if (e.detail.receivers.includes(user.id) || user.id == e
                                    .detail
                                    .sender_id) {
                                    user.last_message = e.detail.media_path ? 'media' : (e
                                        .detail
                                        .message ? e.detail
                                        .message : null); // Update immediately
                                }

                                if (e.detail.receivers.includes(user.id)) {
                                    user.date = e.detail.created_at
                                }
                                return user;
                            });

                            this.users.sort((a, b) => new Date(b.date) - new Date(a.date));
                            console.log('user sorted ');
                        });

                        this.$root.addEventListener('set-unreadCount', (e) => {
                            this.users.find((u) => {
                                if (this.selectedUserId == u.id) {
                                    u.unread_message_count = e.detail.unreadCount
                                }
                            });
                        });

                        this.$nextTick(() => {
                            this.createGlobalChannel();
                        });
                    },


                    /** * Handles user selection and initiates the chat room process.
                     * @param {number} userId - The ID of the user to chat with.
                     */
                    selectUser(userId) {
                        if (this.selectedUserId === userId) return;

                        this.selectedUserId = userId;
                        this.conversationId = null; // Reset chat area while loading
                        this.isLoading = true;

                        // 1. Hit the backend API to find or create the conversation ID
                        axios.post('/chat/access', {
                                recipient_id: userId
                            })
                            .then(response => {
                                this.conversationId = response.data.conversation_id;
                                this.users.find(user => {
                                    if (this.selectedUserId == user.id) {
                                        user.last_message = response.data
                                            .latest_message;
                                    }
                                    return user;
                                });

                                // Dispatch event so Child Component knows to update the data 
                                // We wrap this in $nextTick to ensure the child component exists (after dom rendered) in DOM
                                // this.$nextTick(() => {
                                //     window.dispatchEvent(new CustomEvent('conversation-id', {
                                //         detail: {
                                //             id: this.conversationId
                                //         }
                                //     }));
                                // });
                            })
                            .catch(error => {
                                console.error('Error accessing chat:', error);
                                this.selectedUserId = null; // Reset on failure
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    },
                    createGlobalChannel() {
                        // Connect to 'presence-chat' channel
                        window.Echo.join('global_chat')
                            .here((users) => {
                                // 'users' is the list of everyone currently in the channel
                                this.activeUserIds = users.map(u => u.id);
                            })
                            .joining((user) => {
                                // Push new user ID when they come online
                                if (!this.activeUserIds.includes(user.id)) {
                                    this.activeUserIds.push(user.id);
                                }
                            })
                            .leaving((user) => {
                                // Remove user ID when they go offline
                                this.activeUserIds = this.activeUserIds.filter(id => id !== user.id);
                            })
                            .error((error) => {
                                console.error('Reverb connection error:', error);
                            })
                            .listen('.users.converastion.update', (e) => {
                                console.log('update conversation');
                                console.log(e);
                            });
                    }
                }));

                Alpine.data('searchComponent', () => ({
                    search: '',
                    showSearch: false,
                    filteredUsers: [], // The list we display (starts empty)

                    init() {
                        // 1. Set initial list
                        this.filteredUsers = this.users;

                        // 2. Watch the 'search' variable. 
                        // This code runs ONLY when 'search' changes.
                        this.$watch('search', (value) => {
                            console.log(
                                'Filtering...'); // This will now only show ONCE per keypress

                            if (value === '') {
                                this.filteredUsers = this.users;
                            } else {
                                this.filteredUsers = this.users.filter(user =>
                                    user.name.toLowerCase().includes(value.toLowerCase())
                                );
                            }
                        });
                    },
                    toggleSearch() {
                        this.showSearch = !this.showSearch;
                        if (this.showSearch) {
                            // Wait for the element to render, then focus the input
                            this.$nextTick(() => {
                                this.$refs.searchInput.focus();
                            });
                        } else {
                            // Optional: Clear search when closing
                            this.search = '';
                        }
                    },
                    // createNewConversation() {
                    //     // Placeholder action: Replace this with logic to open a modal 
                    //     // or navigate to a user selection view.
                    //     alert('Opening new conversation modal...');
                    // },
                    /* Add these new properties to your existing x-data object */

                    // // ... existing variables ...
                    // showNewChatModal: false,
                    // newChatSearch: '',

                    // // This represents the list of all contacts you can start a chat with
                    // availableContacts: [{
                    //         id: 101,
                    //         name: 'Alice Smith',
                    //         avatar: ''
                    //     },
                    //     {
                    //         id: 102,
                    //         name: 'Bob Johnson',
                    //         avatar: ''
                    //     },
                    //     // ... fetch this from your backend
                    // ],

                    // // Computed property (or function) to filter contacts in the modal
                    // get filteredContacts() {
                    //     if (this.newChatSearch === '') return this.availableContacts;
                    //     return this.availableContacts.filter(contact =>
                    //         contact.name.toLowerCase().includes(this.newChatSearch.toLowerCase())
                    //     );
                    // },

                    // startNewChat(userId) {
                    //     // Your logic to create a conversation or jump to existing one
                    //     console.log("Starting chat with", userId);
                    //     this.showNewChatModal = false;
                    //     this.selectUser(userId); // Assuming selectUser handles the switching
                    // }


                }));

            });
        </script>
    </x-slot>
</x-app-layout>
