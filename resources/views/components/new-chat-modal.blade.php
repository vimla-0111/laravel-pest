@props(['showNewChatModal'])
<div x-show="showNewChatModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" role="dialog" aria-modal="true" x-data="NewChatModalComponent">

    <div x-show="showNewChatModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"
        @click="showNewChatModal = false"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="showNewChatModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

            <div class="bg-indigo-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">New Message</h3>
                <button @click="showNewChatModal = false" class="text-indigo-200 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-4 pt-4 pb-2">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" x-model="newChatSearch"
                        class="block w-full rounded-md border-0 py-2.5 pl-10 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Search people...">
                </div>
            </div>

            <div class="h-80 overflow-y-auto px-2 pb-4">
                <ul role="list" class="divide-y divide-gray-100">
                    <template x-for="contact in filteredContacts" :key="contact.id">
                        <li @click="startNewChat(contact.id)"
                            class="flex items-center gap-x-4 px-4 py-3 hover:bg-indigo-50 cursor-pointer rounded-lg transition-colors group">
                            <img class="h-10 w-10 flex-none rounded-full bg-gray-50 object-cover"
                                :src="contact.avatar ||
                                    'https://ui-avatars.com/api/?background=random&name=' + contact.name"
                                alt="">
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm font-semibold leading-6 text-gray-900 group-hover:text-indigo-700"
                                    x-text="contact.name"></p>
                                <p class="mt-1 truncate text-xs leading-5 text-gray-500">Available</p>
                            </div>
                            <div class="flex-none items-center">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-gray-300 group-hover:text-indigo-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </li>
                    </template>

                    <template x-if="filteredContacts.length === 0">
                        <div class="text-center py-10">
                            <p class="text-gray-500 text-sm">No contacts found.</p>
                        </div>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('NewChatModalComponent', () => ({
            showNewChatModal: false,
            newChatSearch: '',
            init() {
                console.log('init new chat');
                
            },

            // This represents the list of all contacts you can start a chat with
            availableContacts: [{
                    id: 101,
                    name: 'Alice Smith',
                    avatar: ''
                },
                {
                    id: 102,
                    name: 'Bob Johnson',
                    avatar: ''
                },
                // ... fetch this from your backend
            ],

            // Computed property (or function) to filter contacts in the modal
            get filteredContacts() {
                if (this.newChatSearch === '') return this.availableContacts;
                return this.availableContacts.filter(contact =>
                    contact.name.toLowerCase().includes(this.newChatSearch.toLowerCase())
                );
            },

            startNewChat(userId) {
                // Your logic to create a conversation or jump to existing one
                console.log("Starting chat with", userId);
                this.showNewChatModal = false;
                this.selectUser(userId); // Assuming selectUser handles the switching
            },

        }));
    });
</script>
