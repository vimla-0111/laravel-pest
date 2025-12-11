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
        <div class="w-1/4 border-r border-gray-200  h-screen overflow-y-auto flex flex-col" x-data="searchComponent"
            @start-new-chat.window="handleNewChat($event)">

            <!-- Chat Header with Search and New Chat Buttons -->
            <x-chat-header title="Chats" :onSearch="'toggleSearch()'" :onNewChat="'showNewChatModal = true'" />

            <!-- Search Input Section -->
            <div x-show="showSearch" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="p-2 border-b bg-gray-50" style="display: none;">
                <x-search-input />
            </div>

            <!-- Users List -->
            <div class="divide-y divide-gray-100 flex-1 overflow-y-auto">
                <template x-for="user in filteredUsers" :key="user.id">
                    <x-user-list-item />
                </template>
                <template x-if="filteredUsers.length === 0">
                    <x-empty-state message="No active chats" />
                </template>
            </div>
        </div>

        <div class="w-3/4 flex flex-col">
            <template x-if="selectedUserId">
                <div class="flex flex-col h-full">
                    <!-- Message Header -->
                    <x-message-header />

                    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <template x-if="conversationId">
                            <div class="h-full w-full">
                                <x-chat :conversationId="null" x-bind:conversation-id="conversationId"
                                    x-bind:current-user-id="currentUserId" x-bind:selected-user-id="selectedUserId" />
                            </div>
                        </template>

                        <template x-if="isLoading">
                            <x-loading-spinner />
                        </template>
                    </div>

                    <template x-if="!selectedUserId && !isLoading">
                        <x-empty-state message="Select a user to start chatting." />
                    </template>
                </div>
            </template>

            <template x-if="!selectedUserId">
                <x-empty-state message="Select a user to start chatting." />
            </template>
        </div>
        <x-new-chat-modal x-model="showNewChatModal" />
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
                    showNewChatModal: false,

                    init() {
                        // Listen for event ONLY inside parent component
                        this.$root.addEventListener('set-lastMessage', (e) => {
                            console.log('new message received:', e.detail.message);

                            this.users = this.users.map(user => {
                                // update the last message in users list
                                if (e.detail.receivers.includes(user.id) || user.id == e
                                    .detail
                                    .sender_id) {
                                    user.last_message = e.detail.media_path ? 'media' : (e
                                        .detail
                                        .message ? e.detail
                                        .message : null); // Update immediately
                                }

                                // TODO : need to verify as it sorts the user list when new message sent/received
                                if (e.detail.receivers.includes(user.id)) {
                                    user.date = e.detail.created_at
                                }
                                return user;
                            });

                            this.users.sort((a, b) => new Date(b.date) - new Date(a.date));
                            console.log('user sorted ');
                        });

                        // update the unread count when message read by the reciver
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
                    selectUser(userId, user = null) {
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

                                // update user list when start the new conversation
                                if (user) {
                                    this.users.unshift(user);
                                }
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

                    // Function to handle the event emitted by the Modal
                    handleNewChat(e) {
                        console.log("User selected from modal:", e.detail.user.id, e.detail.user.name);
                        this.selectUser(e.detail.user.id, e.detail
                            .user); // Reuse your existing logic to open the chat
                    },
                }));


                Alpine.store('chatSelection', {
                    active: false, // equivalent to selectionMode
                    selected: [], // equivalent to selectedMessages

                    // Toggle the mode on/off
                    toggle() {
                        console.log(this.selected, this.active);
                        this.active = !this.active;
                        if (!this.active) this.selected = []; // Clear items if turning off

                    },

                    // Add or remove an ID
                    toggleItem(id) {
                        if (this.selected.includes(id)) {
                            this.selected = this.selected.filter(item => item !== id);
                        } else {
                            this.selected.push(id);
                        }
                    },

                    // Reset everything (e.g., after delete)
                    reset() {
                        this.active = false;
                        this.selected = [];
                    }
                });

            });
        </script>
    </x-slot>
</x-app-layout>
