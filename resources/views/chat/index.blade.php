<div class="relative inline-block">
    <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('images/default-avatar.png') }}" 
         alt="Avatar" 
         class="w-12 h-12 rounded-full object-cover border-2 border-red-200 shadow">

    {{-- Status bulat hijau hanya kalau user online --}}
    @if ($user->is_online)
        <span class="absolute bottom-0 right-0 block w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></span>
    @endif
</div>
