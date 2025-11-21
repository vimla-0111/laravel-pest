<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Global Chat Room') }}
        </h2>
    </x-slot>

    <div x-data="mainComponent()" class="bg-white shadow-xl sm:rounded-lg flex h-[80vh] overflow-hidden">
        <div class="w-1/4 border-r border-gray-200 bg-gray-50 overflow-y-auto">
            <h3 class="text-xl font-semibold p-4 border-b">Chats</h3>

            <template x-for="user in users" :key="user.id">
                <button @click="selectUser(user.id)" 
                    class="w-full text-left p-4 flex items-center hover:bg-indigo-50 focus:outline-none"
                    :class="{
                        'bg-indigo-100 font-medium': selectedUserId === user.id,
                        'cursor-default opacity-50': user.id === currentUserId
                    }">
                    <div class="w-10 h-10 bg-gray-400 rounded-full mr-3"></div>
                    <span x-text="user.name"></span>
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
                        <template x-if="conversationId" x-text="console.log('render chat component')">
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
            function mainComponent() {
                return {
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
                    }
                }
            }
        </script>

        {{-- <script>
            function chatComponent(initialConversationId) {
                return {
                    conversationId: initialConversationId,
                    messages: [],
                    newMessage: '',
                    channel: null,

                    // Re-initializes the WebSocket connection when the room changes
                    initEcho() {
                        if (!this.conversationId) return;

                        // 1. Leave any previous channels
                        if (this.channel) {
                            window.Echo.leave(`chat.${this.channel.name.split('.')[1]}`);
                        }
                        console.log('inside init echo');
                        
                        // 2. Connect to the new Private Channel
                        this.channel = window.Echo.private(`chat.${this.conversationId}`)
                            .listen('.chat.message.sent', (e) => {
                                console.log(e);
                                
                                this.messages.push(e);
                                this.scrollToBottom();
                            });
                    },

                    // Fetches initial message history
                    fetchMessages() {
                        if (!this.conversationId) return this.messages = [];

                        axios.get(`/chats/${this.conversationId}/messages`)
                            .then(response => {
                                this.messages = response.data.messages;
                                this.scrollToBottom();
                            })
                            .catch(error => console.error('Failed to fetch messages:', error));
                    },

                    sendMessage() {
                        if (this.newMessage.trim() === '') return;

                        axios.post(`/chats/${this.conversationId}/messages`, {
                            body: this.newMessage
                        }).then(response => {
                            this.newMessage = '';
                            // The message will appear via the Echo listener, ensuring real-time integrity
                        }).catch(error => {
                            console.error('Failed to send message:', error);
                        });
                    },

                    scrollToBottom() {
                        this.$nextTick(() => {
                            const box = this.$refs.scrollBox;
                            if (box) {
                                box.scrollTop = box.scrollHeight;
                            }
                        });
                    }
                }
            }

            function initChatComponent(initialConversationId) {
                // Re-initialize when the parent component changes the conversation ID
                if (initialConversationId !== conversationId) {
                    conversationId = initialConversationId;
                    initEcho();
                    fetchMessages();
                }
            }
        </script> --}}
    </x-slot>
</x-app-layout>
