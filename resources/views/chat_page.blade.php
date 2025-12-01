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
        <div class="w-1/4 border-r border-gray-200 overflow-y-auto">
            <h3 class="text-xl font-semibold p-4 border-b">Chats</h3>

            <template x-for="user in users" :key="user.id">
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
                        <span x-text="user.name" class="font-semibold text-gray-900">
                        </span>
                        </div>

                        <p x-text="user?.last_message || 'No messages yet '"
                                class="text-sm text-gray-500 truncate"
                                :class="{ 'text-indigo-700': selectedUserId === user.id }">
                            </p>
                        </div>
                </button>
            </template>

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
                                    x-bind:current-user-id="currentUserId" />
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
                            console.log('media received:', e.detail.media_path);

                            // this.lastMessage = e.detail; // update parent property

                            this.users = this.users.map(user => {
                                if (e.detail.receivers.includes(user.id) || user.id == e
                                    .detail
                                    .sender_id) {
                                    user.last_message = e.detail.media_path ? 'media' : (e
                                        .detail
                                        .message ? e.detail
                                        .message : null); // Update immediately
                                }
                                return user;
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
                        console.log('change user');
                        
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
                            });
                    }
                }));
            })
        </script>
    </x-slot>
</x-app-layout>
