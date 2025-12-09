@props(['conversationId', 'currentUserId', 'selectedUserId'])

<div x-data="chatComponent(conversationId)" @conversation-id.window="handleConversationChange($event.detail.id)"
    class="flex flex-col h-full w-full bg-white">
    <div x-show="isTyping" class="text-xs text-gray-500 italic p-2">
        <span x-text="typingUser"></span> is typing...
    </div>


    <div x-ref="scrollBox" class="flex-1 overflow-y-auto p-4 min-h-0 space-y-4 bg-gray-50">
        <div x-show="isLoading" class="flex justify-center items-center h-full text-gray-400">
            Loading messages...
        </div>

        <div x-show="!isLoading && messages.length === 0"
            class="flex justify-center items-center h-full text-gray-400 text-sm">
            No messages yet. Say hello!
        </div>

        {{-- 2. FLOATING CONFIRMATION BAR --}}
        <div x-show="$store.chatSelection.active" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
            class="fixed bottom-6 left-0 right-0 z-50 flex justify-center px-4">

            <div
                class="bg-white text-gray-800 shadow-2xl rounded-full px-8 py-3 flex items-center gap-6 border border-gray-100">
                <span class="text-sm font-medium text-gray-500">
                    <span x-text="$store.chatSelection.selected.length"
                        class="text-indigo-600 font-bold text-lg"></span> selected
                </span>

                {{-- Delete Button --}}
                <button @click="deleteFromStore()" :disabled="$store.chatSelection.selected.length === 0"
                    class="flex items-center gap-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-full font-medium text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>

                {{-- Cancel --}}
                <button @click="$store.chatSelection.toggle()" class="text-gray-400 hover:text-gray-600 text-sm">
                    Cancel
                </button>
            </div>
        </div>

        {{-- 3. MESSAGE LIST --}}
        <div class="flex-1 p-4 pb-24 overflow-y-auto">
            <template x-for="(msg, index) in messages" :key="msg.id">
                <div class="w-full flex flex-col">

                    {{-- Date Divider --}}
                    <div x-show="isNewDate(index)" class="flex justify-center my-4">
                        <span x-text="formatDateLabel(msg.created_at)"
                            class="text-[11px] font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                        </span>
                    </div>

                    <div class="flex w-full mb-2 transition-all duration-300"
                        :class="msg.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
                        x-intersect.once.threshold.50="handleIntersect(msg.id,msg)">

                        {{-- CHECKBOX AREA (Slides in) --}}
                        <div x-show="$store.chatSelection.active" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-x-4 w-0"
                            x-transition:enter-end="opacity-100 translate-x-0 w-8"
                            class="flex items-center justify-center mr-3 shrink-0 overflow-hidden">
                            <input type="checkbox" :value="msg.id"
                                :checked="$store.chatSelection.selected.includes(msg.id)"
                                @change="$store.chatSelection.toggleItem(msg.id)"
                                class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </div>

                        {{-- MESSAGE BUBBLE --}}
                        {{-- Added @click handler and conditional classes for selection --}}
                        <div class="max-w-[75%] rounded-2xl px-4 py-2 shadow-sm text-sm relative transition-all duration-200 border"
                            @click="$store.chatSelection.toggleItem(msg.id)"
                            :class="[
                                msg.sender_id === currentUserId ?
                                'bg-indigo-600 text-white rounded-br-none border-transparent' :
                                'bg-white text-gray-800 border-gray-200 rounded-bl-none',
                                $store.chatSelection.active ? 'cursor-pointer hover:opacity-90' : '',
                                $store.chatSelection.active && $store.chatSelection.selected.includes(msg.id) ?
                                'ring-2 ring-indigo-400 ring-offset-2' : ''
                            ]">

                            {{-- Media Attachment --}}
                            <template x-if="msg.media_path">
                                <div class="mb-2">
                                    {{-- Disable link interaction when in selection mode --}}
                                    <a :href="$store.chatSelection.active ? 'javascript:void(0)' : msg.media_path"
                                        :target="$store.chatSelection.active ? '' : '_blank'" class="block">
                                        <img :src="msg.media_path"
                                            class="rounded-lg object-cover w-48 h-32 md:w-64 md:h-40 border"
                                            :class="msg.sender_id === currentUserId ? 'border-indigo-500' : 'border-gray-200'"
                                            alt="Attachment">
                                    </a>
                                </div>
                            </template>

                            {{-- Text Message --}}
                            <template x-if="msg.message">
                                <div class="flex items-center gap-2 relative">
                                    <p x-text="msg.message" class="leading-relaxed"></p>
                                    {{-- OLD DELETE BUTTON REMOVED HERE --}}
                                </div>
                            </template>

                            {{-- Time & Read Receipts --}}
                            <div class="flex items-center justify-end gap-1 mt-1 select-none">
                                <span class="text-[10px] opacity-70" x-text="formatTime(msg.created_at)"></span>

                                <template x-if="msg.sender_id === currentUserId">
                                    <div class="flex items-center">
                                        <template x-if="msg.read_at">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="w-3.5 h-3.5 text-blue-300">
                                                <path fill-rule="evenodd"
                                                    d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z"
                                                    clip-rule="evenodd" />
                                                <path
                                                    d="M10.958 10.093l.036-.057 4.29-6.435a.75.75 0 011.248.832l-4.29 6.435-1.284-.775z" />
                                            </svg>
                                        </template>
                                        <template x-if="!msg.read_at">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="w-3.5 h-3.5 text-indigo-200 opacity-70">
                                                <path fill-rule="evenodd"
                                                    d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex-none p-3 bg-white border-t border-gray-200 z-20">
        <div x-data="{
            selectedImage: '',
            previewUrl: null,
            pickImage() { this.$refs.fileInput.click(); },
            handleImage(e) {
                const file = e.target.files[0];
                if (!file) return;
        
                this.selectedImage = file;
                this.previewUrl = URL.createObjectURL(file);
            },
            removeImage() {
                this.selectedImage = '';
                this.previewUrl = null;
                this.$refs.fileInput.value = null;
            },
        }" class="relative flex items-center">

            <!-- HIDDEN FILE INPUT -->
            <input type="file" multiple accept="image/*" class="hidden" x-ref="fileInput" @change="handleImage">

            <!-- MESSAGE INPUT -->
            <input type="text" x-model="newMessage" @keydown.enter.prevent="sendMessage"
                placeholder="Type your message..."
                class="w-full bg-gray-100 border-0 rounded-full py-3 pl-12 pr-12 focus:ring-2 
               focus:ring-indigo-500 focus:bg-white transition-all"
                :disabled="!conversationId || isLoading">

            <!-- IMAGE PICK BUTTON -->
            <button @click="pickImage"
                class="absolute left-3 p-1 text-indigo-600 bg-indigo-100 rounded-full hover:bg-indigo-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>

            <!-- SEND BUTTON -->
            <button @click="sendMessage" :disabled="(!conversationId) || (!newMessage.trim() && !selectedImage)"
                class="absolute right-2 p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 
               disabled:opacity-50 transition-colors">
                <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>

            <!-- IMAGE PREVIEW -->
            <template x-if="previewUrl">
                <div class="absolute -top-24 left-0 bg-white shadow-lg rounded-lg overflow-hidden p-2 w-32">
                    <img :src="previewUrl" class="rounded-md w-full h-auto">

                    <!-- Remove Image -->
                    <button @click="removeImage"
                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full px-1 text-xs">✕</button>
                </div>
            </template>
        </div>
    </div>
</div>
<script>
    function chatComponent(initialId) {
        return {
            conversationId: initialId,
            messages: [],
            newMessage: '',
            isLoading: false,
            channel: null,
            typingTimeout: null,
            isTyping: false,
            typingUser: '',
            isConversationLoading: false,
            readMessages: [],
            timer: null,
            unreadCount: 0,

            // init run every time the conversationId changes when selecting a new user because chat component flushed when conversationId is set to null and re iniatlize when conversationId is set to new value otherwise init called only once when component initializes
            init() {
                console.log('init conversation');

                if (this.conversationId) {
                    this.setupConversation();
                }
                this.$nextTick(() => {

                    //fetch messages when websocket connection reconnected
                    window.Echo.connector.pusher.connection.bind('state_change', (
                        states) => {
                        // states = { previous: 'disconnected', current: 'connected' }
                        console.log(states);

                        if (states.previous === 'unavailable' && states.current ===
                            'connected') {
                            console.log(
                                'Connection restored! Fetching missed data...');
                            this.fetchMessages();
                        }
                    });
                });
            },

            handleIntersect(messageId, msg) {
                this.$nextTick(() => {
                    if (!this.isConversationLoading && msg.sender_id !== this.currentUserId && !msg.read_at) {
                        this.markAsRead(messageId, msg.message);
                    }
                });

            },

            markAsRead(messageId, chat) {
                console.log(messageId, chat);
                this.readMessages.push({
                    'messageId': messageId,
                    'read_at': new Date().toISOString()
                });

                console.log('length is' + this.readMessages.length);

                if (this.readMessages.length) {
                    if (this.timer == null) {
                        console.log('timer is null');

                        this.timer = setTimeout(() => {
                            this.sendMarkAsReadRequests();
                        }, 2000);
                    } else {
                        console.log('timer exists');
                    }
                }
            },

            sendMarkAsReadRequests() {
                this.timer = null;
                let payload = {
                    messages: this.readMessages,
                    headers: {
                        'X-Socket-ID': window.Echo.socketId()
                    }
                };
                this.readMessages = []; // Clear immediately to prevent duplicate calls

                axios.post("{{ route('chat.mark.read') }}", payload)
                    .then(response => {
                        console.log('read done');
                    })
                    .catch(error => {
                        console.error("Failed to mark messages as read", error);
                    });
            },

            // called when parent's custom event triggered
            handleConversationChange(newId) {
                console.log('handle conversation');

                if (newId !== this.conversationId) {
                    this.conversationId = newId;
                    this.setupConversation();
                }
            },

            setupConversation() {
                this.messages = []; // Clear old messages immediately
                this.isLoading = true;
                this.initEcho();
                this.fetchMessages();
            },

            initEcho() {
                // Leave previous channel if exists
                // only required when update component by triggering event
                // when component initialize every time the conversation changed then it create new channel
                if (this.channel) {
                    console.log('leave ');
                    window.Echo.leave(`chat.${this.channel.name.split('.')[1]}`);
                }

                // Join new channel
                this.channel = window.Echo.private(`chat.${this.conversationId}`)
                    .listen('.chat.message.sent', (e) => {
                        this.messages.push(e);
                        this.$dispatch('set-lastMessage', {
                            receivers: e.receivers,
                            sender_id: e.sender_id,
                            message: e.message,
                            media_path: e.media_path,
                            created_at: e.created_at
                        });
                        this.scrollToBottom();
                    }).listen('.chat.message.read', (e) => {
                        console.log('message read event received');
                        this.messages = this.messages.map(function(message) {
                            return message.id === e.id ? {
                                ...message,
                                read_at: e.read_at,
                            } : message;
                        });


                        this.unreadCount = this.messages.filter(m => !m.read_at &&
                            m.sender_id != this.currentUserId
                        ).length;

                        this.$dispatch('set-unreadCount', {
                            unreadCount: this.unreadCount
                        });
                    }).listen('.chat.message.delete', (e) => {
                        console.log('message delete event received', e);
                        this.messages = this.messages.filter(m => !e.ids.includes(m.id));
                    }).listenForWhisper('typing', (e) => {
                        this.typingUser = e.name;
                        this.isTyping = true;

                        // Clear any existing timeout
                        clearTimeout(this.typingTimeout);

                        // Set a new timeout to hide the indicator after 3 seconds
                        this.typingTimeout = setTimeout(() => {
                            this.isTyping = false;
                            this.typingUser = '';
                        }, 5000);
                    });
            },

            fetchMessages() {
                this.isConversationLoading = true;
                // this.scrollToBottom();
                axios.get(`/chats/${this.conversationId}/messages`)
                    .then(response => {
                        this.messages = response.data.messages || response.data;
                        this.isLoading = false;
                        this.scrollToBottom();
                        this.isConversationLoading = false;
                    })
                    .catch(error => {
                        console.error(error);
                        this.isLoading = false;
                        this.isConversationLoading = false;
                    });
            },

            sendMessage() {

                // if (this.newMessage.trim() === '') return;
                const formData = new FormData();
                formData.append('body', this.newMessage)
                formData.append('image', this.selectedImage);
                formData.append('selectedUserId', this.selectedUserId);

                this.newMessage = ''; // Clear input immediately    
                this.removeImage(); // Clear selected image immediately

                axios.post(`/chats/${this.conversationId}/messages`, formData)
                    .catch(error => {
                        console.error('Message failed:', error);
                        alert(error.response.data.message);
                    });
            },
            /* Inside your x-data object */

            deleteMessage(messageId) {
                if (confirm('Are you sure you want to delete this message?')) {
                    // 1. Remove from local UI immediately for responsiveness
                    this.messages = this.messages.filter(m => m.id !== messageId);

                    // 2. Send request to backend (Example)
                    // fetch(`/api/messages/${messageId}`, { method: 'DELETE' });

                    console.log('Deleted message:', messageId);
                }
            },

            startTyping() {
                // Only whisper if the message input is not empty
                if (this.newMessage.length > 0) {
                    window.Echo.private(`chat.${this.conversationId}`)
                        .whisper('typing', {
                            name: '{{ auth()->user()->name }}'
                            // Ensure the user is authenticated and has a name attribute
                        });
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const box = this.$refs.scrollBox;
                    if (box) {
                        box.scrollTop = box.scrollHeight;
                    }
                });
            },

            /**
             * Checks if the current message has a different date than the previous one.
             */
            isNewDate(index) {
                // Always show date for the very first message
                if (index === 0) return true;

                const currentMsgDate = new Date(this.messages[index].created_at).toDateString();
                const previousMsgDate = new Date(this.messages[index - 1].created_at).toDateString();

                return currentMsgDate !== previousMsgDate;
            },

            /**
             * Formats the date into "Today", "Yesterday", or "Month Day, Year"
             */
            formatDateLabel(dateString) {
                const date = new Date(dateString);
                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(today.getDate() - 1);

                if (date.toDateString() === today.toDateString()) {
                    return 'Today';
                } else if (date.toDateString() === yesterday.toDateString()) {
                    return 'Yesterday';
                } else {
                    // Returns format like: Nov 24, 2025
                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                }
            },

            formatTime(dateString) {
                return new Date(dateString).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            debounce(func, delay = 300) {
                let timer;
                return (...args) => {
                    if (!timer) {
                        func.apply(this, args);
                    }
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        timer = undefined;
                    }, delay);
                };
            },
            async deleteFromStore() {
                // Get IDs from the global store
                const ids = Alpine.store('chatSelection').selected;

                if (!ids.length || !confirm('Delete selected?')) return;

                // ... Perform backend deletion ...
                const payload = {
                    'ids': ids,
                    'conversation_id': this.conversationId,
                    headers: {
                        'X-Socket-ID': window.Echo.socketId()
                    }
                }
                axios.post("{{ route('chat.delete') }}", payload)
                    .then(response => {
                        console.log(response.data.message);
                    })
                    .catch(error => {
                        console.error("Failed to delete messages as read", error);
                    });


                // Update UI
                this.messages = this.messages.filter(m => !ids.includes(m.id));

                // RESET THE STORE (This automatically turns off the blue button in the header!)
                Alpine.store('chatSelection').reset();
            }
        }
    }
</script>
