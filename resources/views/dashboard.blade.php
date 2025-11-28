{{-- <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout> --}}


{{-- <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @foreach ($posts as $post)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    {{ $post->title }}
                                </h3>
                                
                                <div class="text-sm text-gray-500 mb-4">
                                    <span>Published: {{ $post->formatted_published_at }}</span>
                                    <span class="mx-2">&bull;</span>
                                    <span>Author: {{ $post?->creator->name ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-700 leading-relaxed">
                            {{ Str::limit($post->content, 200) }}
                        </p>
                    </div>
                </div>
            @endforeach

            <div class="mt-6">
                {{ $posts->links() }}
            </div>

        </div>
    </div>
</x-app-layout> --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="text-xl font-semibold p-4 border-b">Latest Posts</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                @foreach ($posts as $post)
                    <div
                        class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden flex flex-col h-full border border-gray-100">

                        <div class="p-6 flex-grow">
                            {{-- <div class="mb-3">
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    Post
                                </span>
                            </div> --}}

                            <h3 class="text-lg font-bold text-gray-900 mb-3 leading-tight">
                                <a href="{{ route('posts.show', $post->id) }}"
                                    class="hover:text-indigo-600 transition-colors duration-200">
                                    {{ $post->title }}
                                </a>
                            </h3>

                            <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                                {{ Str::limit($post->content, 120) }}
                            </p>
                        </div>

                        <div
                            class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                            <div class="flex items-center">
                                <div
                                    class="h-6 w-6 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mr-2 uppercase text-[10px]">
                                    {{ substr($post?->creator->name ?? 'U', 0, 1) }}
                                </div>
                                <span>{{ $post?->creator->name ?? 'Unknown' }}</span>
                            </div>
                            <span>{{ $post->formatted_published_at }}</span>
                        </div>
                    </div>
                @endforeach

            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
