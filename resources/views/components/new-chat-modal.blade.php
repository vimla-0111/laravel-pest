<div x-data="newConversationModal" x-modelable="open" x-show="open" style="display: none;"
    {{ $attributes->merge(['class' => 'fixed inset-0 z-50 overflow-y-auto']) }} aria-labelledby="modal-title"
    role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="open = false">
    </div>

    <!-- Modal Panel -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="open" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">New Conversation</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="p-4 bg-gray-50 border-b border-gray-100">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" x-model.debounce.1000ms="searchQuery"
                        class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Search users by name or email...">
                </div>
            </div>

            <!-- User List -->
            <ul role="list" class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                <!-- Loading State -->
                <template x-if="isLoading">
                    <div class="p-8 text-center text-gray-500 text-sm flex flex-col items-center">
                        <svg class="animate-spin h-6 w-6 text-indigo-600 mb-2" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Loading users...
                    </div>
                </template>

                <!-- Users Loop -->
                <template x-for="user in filteredUsers" :key="user.id">
                    <li @click="startConversation(user)"
                        class="flex items-center gap-x-4 px-4 py-4 hover:bg-gray-50 cursor-pointer transition-colors group">

                        <div class="w-10 h-10 bg-gray-400 rounded-full flex-shrink-0 overflow-hidden">
                            <img :src="user.avatar ||
                                'https://ui-avatars.com/api/?name=' + user.name"
                                alt="" class="w-full h-full object-cover">
                        </div>

                        {{-- <img class="h-10 w-10 flex-none rounded-full bg-gray-50" :src="user.avatar" alt=""> --}}
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-gray-900 group-hover:text-indigo-600"
                                x-text="user.name"></p>
                            {{-- <p class="mt-1 truncate text-xs leading-5 text-gray-500" x-text="user.email"></p> --}}
                        </div>
                        <div class="flex-none items-center">
                            <span
                                class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 group-hover:ring-indigo-600/20 group-hover:text-indigo-600 group-hover:bg-indigo-50">
                                Start Chat
                            </span>
                        </div>
                    </li>
                </template>

                <!-- Empty State -->
                <template x-if="!isLoading && filteredUsers.length === 0">
                    <div class="p-8 text-center text-gray-500 text-sm">
                        No users found matching "<span x-text="searchQuery" class="font-semibold"></span>"
                    </div>
                </template>
            </ul>
        </div>
    </div>
</div>
<script>
    function newConversationModal() {
        return {
            open: false,
            searchQuery: '',
            users: [],
            isLoading: false,
            filteredUsers: [],

            // 1. Initialize: Fetch users when component loads (or when modal opens)
            init() {
                console.log('open modal');
                this.isLoading = true;

                this.$nextTick(() => {
                    axios.get("{{ route('chat.users') }}")
                        .then(response => {
                            this.users = response.data.users;
                            this.filteredUsers = this.users;
                            this.isLoading = false;

                        })
                        .catch(error => {
                            console.error(error);
                            this.isLoading = false;
                        });
                });

                // This code runs ONLY when 'searchQuery' changes.
                this.$watch('searchQuery', (value) => {

                    if (this.searchQuery === '') {
                        this.filteredUsers = this.users;
                    } else {
                        this.filteredUsers = this.users.filter(user => {
                            return user.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                        });
                    }
                });

            },

            // 3. Selection Logic
            startConversation(user) {
                // Emit an event to the parent component to switch chat
                this.$dispatch('start-new-chat', {
                    user: user
                });
                this.open = false; // Close modal
                this.searchQuery = ''; // Reset search

                this.users = this.users.filter(u => u.id !== user.id);
                this.filteredUsers = this.users;
            }
        };
    }
</script>
