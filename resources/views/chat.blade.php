<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Global Chat Room') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="chatComponent({{ auth()->id() }})" x-init="start()">
                <div x-show="isTyping" class="text-xs text-gray-500 italic p-2">
                    <span x-text="typingUser"></span> is typing...
                </div>
                <div class="p-6 text-gray-900">

                    <div class="h-96 overflow-y-auto border-b border-gray-200 mb-4 p-4 flex flex-col space-y-2"
                        x-ref="chatBox">
                        <template x-for="msg in messages" :key="msg.id">
                            <div class="flex flex-col"
                                :class="msg.user_id === currentUserId ? 'items-end' : 'items-start'">
                                <span class="text-xs text-gray-500" x-text="msg.user_name"></span>
                                <div class="px-4 py-2 rounded-lg max-w-xs"
                                    :class="msg.user_id === currentUserId ? 'bg-blue-500 text-white' :
                                        'bg-gray-200 text-gray-800'"
                                    x-text="msg.text"></div>
                            </div>
                        </template>
                    </div>

                    <form @submit.prevent="sendMessage" class="flex gap-4">
                        <input type="text" x-model="newMessage" @keydown.debounce.500ms="startTyping"
                            @keyup.debounce.1000ms="stopTyping"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Type your message...">
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            :disabled="!newMessage">
                            Send
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
    <script>
        function chatComponent(currentUserId) {
            console.log(@json($messages));

            return {
                currentUserId: currentUserId,
                messages: @json($messages), // Loaded from DB via Blade
                newMessage: '',
                typingTimeout: null,
                isTyping: false,
                typingUser: '',

                init() {
                    console.log('init called');
                },
                start() {
                    // 1. Scroll to bottom on load
                    this.$nextTick(() => this.scrollToBottom());
                    console.log('start called');

                    // 2. Listen to Reverb Channel
                    window.Echo.channel('chat')
                        .listen('.message.sent', (e) => {
                            this.messages.push({
                                id: e.id,
                                text: e.text,
                                user_name: e.user_name,
                                user_id: e.user_id
                            });
                            console.log('ss');
                            console.log(this.messages);

                            this.$nextTick(() => this.scrollToBottom());
                        });

                    window.Echo.channel('public')
                        .listen('.message.sent', (e) => {
                            console.log('recieved new message');
                            console.log(e.text);
                        });

                    window.Echo.private('private_chat')
                        .listen('.message.sent', (e) => {
                            console.log('recieved new private message');
                            console.log(e.text);
                        }).listenForWhisper('typing', (e) => {
                            this.typingUser = e.name;
                            this.isTyping = true;

                            // Clear any existing timeout
                            clearTimeout(this.typingTimeout);

                            // Set a new timeout to hide the indicator after 3 seconds
                            this.typingTimeout = setTimeout(() => {
                                this.isTyping = false;
                                this.typingUser = '';
                            }, 3000);
                        });
                },

                sendMessage() {
                    if (this.newMessage.trim() === '') return;

                    const text = this.newMessage;
                    this.newMessage = ''; // Clear input immediately

                    // Optimistic UI Update (Optional: add locally before server confirms)
                    // this.messages.push({
                    //     id: Date.now(),
                    //     text: text,
                    //     user_name: '{{ auth()->user()->name }}',
                    //     user_id: this.currentUserId
                    // });
                    this.$nextTick(() => this.scrollToBottom());

                    // Send to Server
                    axios.post('/chat', {
                            text: text
                        }, {
                            headers: {
                                'X-Socket-ID': Echo.socketId()
                            }
                        })
                        .catch(error => {
                            console.error('Message sending failed:', error);
                        });
                },

                startTyping() {
                    // Only whisper if the message input is not empty
                    if (this.newMessage.length > 0) {
                        window.Echo.private('private_chat')
                            .whisper('typing', {
                                name: '{{ Auth::user()->name }}'
                                // Ensure the user is authenticated and has a name attribute
                            });
                    }
                },
                
                stopTyping() {
                    // You can optionally send a 'stop-typing' whisper here if needed, 
                    // but usually, a client-side timeout is sufficient for good UX.
                },
                scrollToBottom() {
                    this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight;
                }
            }
        }
    </script>
</x-app-layout>
