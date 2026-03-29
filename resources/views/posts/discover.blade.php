<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Discover Posts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <form method="GET" action="{{ route('posts.discover') }}"
                        class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <x-input-label for="search" :value="__('Search published posts')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full"
                                :value="$search" placeholder="Search by title or content" />
                        </div>

                        <div class="flex items-end gap-3">
                            <x-primary-button>
                                {{ __('Search') }}
                            </x-primary-button>

                            @if ($search !== '')
                                <a href="{{ route('posts.discover') }}"
                                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="text-sm text-gray-600">
                        @if ($search !== '')
                            {{ trans_choice(':count published post found|:count published posts found', $posts->total(), ['count' => $posts->total()]) }}
                            {{ __('for') }} "<span class="font-medium text-gray-900">{{ $search }}</span>".
                        @else
                            {{ trans_choice(':count published post available|:count published posts available', $posts->total(), ['count' => $posts->total()]) }}.
                        @endif
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Title') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Body') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Author') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Published At') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($posts as $post)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="{{ route('posts.show', $post) }}"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            {{ $post->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ Str::limit($post->content, 80) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $post->creator?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $post->formatted_published_at ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        {{ __('No published posts found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
