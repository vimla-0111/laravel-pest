@props(['conversationId', 'currentUserId'])

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

        <template x-for="(msg, index) in messages" :key="msg.id">
            <div class="w-full flex flex-col">

                <div x-show="isNewDate(index)" class="flex justify-center my-4">
                    <span x-text="formatDateLabel(msg.created_at)"
                        class="text-[11px] font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                    </span>
                </div>

                <div class="flex w-full mb-2"
                    :class="msg.sender_id === currentUserId ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[75%] rounded-2xl px-4 py-2 shadow-sm text-sm"
                        :class="msg.sender_id === currentUserId ?
                            'bg-indigo-600 text-white rounded-br-none' :
                            'bg-white text-gray-800 border border-gray-200 rounded-bl-none'">

                        <template x-if="msg.media_path">
                            <div class="mb-2">
                                <a :href="msg.media_path" target="_blank" class="block">
                                    <img :src="msg.media_path"
                                        class="rounded-lg object-cover 
                                        w-48 h-32 md:w-64 md:h-40       border"
                                        :class="msg.sender_id === currentUserId ? 'border-indigo-500' : 'border-gray-200'"
                                        alt="Attachment" onerror="">
                                </a>
                            </div>
                        </template>
                        <template x-if="msg.message">
                            <p x-text="msg.message" class="leading-relaxed"></p>
                        </template>
                        <span class="block text-[10px] mt-1 opacity-70 text-right" x-text="formatTime(msg.created_at)">
                        </span>
                    </div>
                </div>
            </div>
        </template>
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
            <input type="file" accept="image/*" class="hidden" x-ref="fileInput" @change="handleImage">

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
                            message: e.message
                        });
                        this.scrollToBottom();
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
                axios.get(`/chats/${this.conversationId}/messages`)
                    .then(response => {
                        this.messages = response.data.messages || response.data;
                        this.isLoading = false;
                        this.scrollToBottom();
                    })
                    .catch(error => {
                        console.error(error);
                        this.isLoading = false;
                    });
            },

            sendMessage() {

                // if (this.newMessage.trim() === '') return;
                const formData = new FormData();
                formData.append('body', this.newMessage)
                formData.append('image', this.selectedImage);

                this.newMessage = ''; // Clear input immediately    
                this.removeImage(); // Clear selected image immediately

                axios.post(`/chats/${this.conversationId}/messages`, formData)
                    .catch(error => {
                        console.error('Message failed:', error);
                        alert(error.response.data.message);
                    });
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
            }
        }
    }
</script>
