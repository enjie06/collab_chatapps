<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $conversation->title ?? 'Percakapan' }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto mt-6">
        <div class="bg-white p-4 rounded-lg border max-h-[60vh] overflow-y-auto">
            @foreach($conversation->messages as $message)
                <div class="mb-3">
                    <strong class="text-purple-700">{{ $message->user->name }}</strong>
                    <p>{{ $message->content }}</p>
                    <small class="text-gray-500 text-xs">{{ $message->created_at->format('H:i') }}</small>
                </div>
            @endforeach
        </div>

        <form action="{{ route('chat.send', $conversation->id) }}" method="POST" class="mt-4 flex">
            @csrf
            <input type="text" name="content" class="flex-1 border rounded-l-lg p-2"
                   placeholder="Tulis pesan..." required>

            <button class="bg-purple-600 text-white px-4 rounded-r-lg hover:bg-purple-700">
                Kirim
            </button>
        </form>
    </div>

<script>
    Echo.channel('conversation.{{ $conversation->id }}')
        .listen('MessageSent', (e) => {
            window.location.reload();
        });
</script>

</x-app-layout>
