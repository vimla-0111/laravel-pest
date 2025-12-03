<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Post Details') }}
            </h2>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                &larr; Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 md:p-12">

                    <header class="mb-8 border-b border-gray-100 pb-8">
                        <span class="text-indigo-600 font-bold tracking-wide uppercase text-xs">
                            Article
                        </span>

                        <h1 class="mt-2 text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight mb-4">
                            {{ $post->title }}
                        </h1>

                        <div class="flex items-center mt-6">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                {{ substr($post?->creator->name ?? 'U', 0, 1) }}
                            </div>

                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $post?->creator->name ?? 'Unknown Author' }}
                                </p>
                                <div class="flex space-x-1 text-sm text-gray-500">
                                    <time datetime="{{ $post->formatted_published_at }}">
                                        {{ $post->created_at->format('F j, Y') }}
                                    </time>
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </header>

                    <article class="prose max-w-none text-gray-800 text-lg leading-8">
                        {!! nl2br(e($post->content)) !!}
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
