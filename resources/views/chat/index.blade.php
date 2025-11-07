<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Percakapan Anda
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto mt-6">
        @forelse($conversations as $conversation)
            <a href="{{ route('chat.show', $conversation->id) }}"
               class="block p-4 mb-3 rounded-lg border bg-white hover:bg-purple-50 transition">

                <strong class="text-purple-700">
                    {{ $conversation->title ?? 'Chat Tanpa Judul' }}
                </strong>

                <div class="text-sm text-gray-500">
                    {{ $conversation->users->pluck('name')->join(', ') }}
                </div>
            </a>
        @empty
            <p class="text-center text-gray-600">
                Belum ada percakapan 😿
            </p>
        @endforelse
    </div>
</x-app-layout>
