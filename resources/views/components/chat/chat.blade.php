@props(['conversationId', 'currentUserId'])

<div x-data="chatComponent({{ $conversationId }})" x-init="initEcho();
fetchMessages();"
    @conversation-id.window="
        // Re-initialize when the parent component changes the conversation ID
        if ($event.detail.id !== conversationId) {
            conversationId = $event.detail.id;
            initEcho();
            fetchMessages();
        }"
    class="flex flex-col h-full">
    <div x-ref="scrollBox" class="flex-grow p-4 space-y-3 overflow-y-auto bg-gray-50">
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex" :class="msg.sender_id === {{ $currentUserId }} ? 'justify-end' : 'justify-start'">
                <div class="max-w-xs p-3 rounded-lg shadow"
                    :class="msg.sender_id === {{ $currentUserId }} ? 'bg-indigo-500 text-white' : 'bg-white text-gray-800'">
                    <p x-text="msg.body"></p>
                    <span class="text-xs opacity-75 mt-1 block text-right"
                        x-text="new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})">
                    </span>
                </div>
            </div>
        </template>
    </div>

    <div class="p-4 bg-white border-t">
        <div class="flex">
            <input type="text" x-model="newMessage" @keydown.enter.prevent="sendMessage"
                placeholder="Type your message..."
                class="flex-grow border border-gray-300 rounded-l-lg p-3 focus:ring-indigo-500 focus:border-indigo-500"
                :disabled="!conversationId">
            <button @click="sendMessage" :disabled="!conversationId || newMessage.trim() === ''"
                class="bg-indigo-600 text-white px-6 rounded-r-lg hover:bg-indigo-700 disabled:opacity-50 transition duration-150">
                Send
            </button>
        </div>
    </div>
</div>

<script>
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

                // 2. Connect to the new Private Channel
                this.channel = window.Echo.private(`chat.${this.conversationId}`)
                    .listen('MessageSent', (e) => {
                        this.messages.push(e);
                        this.scrollToBottom();
                    });
            },

            // Fetches initial message history
            fetchMessages() {
                if (!this.conversationId) return this.messages = [];

                axios.get(`/conversations/${this.conversationId}/messages`)
                    .then(response => {
                        this.messages = response.data.messages;
                        this.scrollToBottom();
                    })
                    .catch(error => console.error('Failed to fetch messages:', error));
            },

            sendMessage() {
                if (this.newMessage.trim() === '') return;

                axios.post(`/conversations/${this.conversationId}/messages`, {
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
</script>
