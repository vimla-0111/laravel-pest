@props(['user', 'isSelected' => false, 'isCurrentUser' => false, 'isActive' => false])

<div class="relative mr-3">
    <div class="w-10 h-10 bg-gray-400 rounded-full flex-shrink-0 overflow-hidden">
        <img :src="user.avatar || 'https://ui-avatars.com/api/?name=' + user.name" 
            alt="{{ $user['name'] ?? '' }}" 
            class="w-full h-full object-cover">
    </div>

    @if ($isActive)
        <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-green-500"></span>
    @endif
</div>
