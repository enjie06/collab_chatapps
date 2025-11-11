<x-app-layout>
    @php
        $otherUser = $conversation->users->firstWhere('id', '!=', auth()->id());
        $isFriend = $otherUser
            ? \App\Models\Friendship::between(auth()->id(), $otherUser->id)
                ->where('status', 'accepted')
                ->exists()
            : false;
    @endphp

    <div class="max-w-3xl mx-auto mt-2 flex flex-col h-[calc(100vh-110px)] space-y-2">

        <!-- Header Chat -->
        <div class="flex items-center justify-between bg-white border-b p-3 sticky top-0 z-10">

            <a href="{{ route('chat.index') }}" class="text-2xl text-rose-600 hover:text-rose-800 pr-2">←</a>

            <div class="flex items-center gap-3 flex-1">
                <img src="{{ $otherUser?->avatar ? asset('storage/'.$otherUser->avatar) : asset('images/default-avatar.png') }}"
                    class="w-10 h-10 rounded-full object-cover border">

                <div class="leading-tight">
                    <p class="font-semibold text-gray-800">{{ $otherUser->name }}</p>
                    <p class="text-xs {{ $otherUser && $otherUser->is_online ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $otherUser && $otherUser->is_online ? 'Online' : 'Offline' }}
                    </p>
                </div>
            </div>

            <div class="relative">
                <button id="menuToggle" class="text-xl px-2 text-gray-600 hover:text-gray-800">⋮</button>

                <div id="menuDropdown"
                    class="hidden absolute right-0 mt-2 w-44 bg-white border rounded-lg shadow-lg p-3">
                    <p class="font-semibold text-center">{{ $otherUser->name }}</p>
                    <p class="text-xs text-gray-500 text-center mb-2">{{ $otherUser->email }}</p>

                    <button type="button" id="viewProfile" class="block text-left w-full hover:text-rose-600 text-sm">
                        Lihat Profil
                    </button>
                    <button type="button" id="deleteChat" class="block text-left w-full hover:text-rose-600 text-sm">
                        Hapus Percakapan
                    </button>
                    <button type="button" id="blockUser" class="block text-left w-full hover:text-red-600 text-sm">
                        Blokir
                    </button>
                </div>
            </div>

        </div>

        <!-- Chat Messages -->
        <div class="bg-white p-4 rounded-lg border h-[200vh] overflow-y-auto" id="chat-body">
            @php $isUnreadBoundaryShown = false; @endphp

            @foreach($conversation->messages as $message)
                @php $isMe = $message->user_id === auth()->id(); @endphp

                {{-- Tanda Pesan Baru --}}
                @if(!$isUnreadBoundaryShown && $message->id > $lastRead && $message->user_id != auth()->id())
                    <div id="unread-marker" class="text-center my-3">
                        <span class="px-3 py-1 text-xs bg-rose-200 text-rose-700 rounded-full">
                            Pesan Baru
                        </span>
                    </div>
                    @php $isUnreadBoundaryShown = true; @endphp
                @endif

                <!-- Bubble -->
                <div class="mb-2 flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[65%] px-2 py-1 rounded-xl leading-snug break-words
                        {{ $isMe ? 'bg-rose-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none' }}">
                        {{ $message->content }}

                        <div class="text-[10px] opacity-70 mt-1 text-right">
                            {{ $message->created_at->format('H:i') }}
                        </div>
                    </div>
                </div>

            @endforeach
        </div>

        <!-- Form kirim pesan -->
        @if($isFriend)
            <form action="{{ route('chat.send', $conversation->id) }}" method="POST"
                class="flex gap-2 p-2 border-t bg-white sticky bottom-0"
                onsubmit="setTimeout(scrollChatToBottom, 50)">
                @csrf
                <textarea name="content" id="chatInput"
                    class="flex-1 border rounded-lg px-3 py-2 focus:border-rose-500 resize-none overflow-hidden leading-tight text-[14px]"
                    placeholder="Tulis pesan..." required></textarea>
                <button class="bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-700 text-[14px]">
                    Kirim
                </button>
            </form>
        @else
            <div class="text-center text-gray-400 text-sm italic py-3 sticky bottom-0 bg-white border-t">
                Kalian bukan teman lagi. Chat hanya dapat dibaca.
            </div>
        @endif
    </div>

    <script>
        const conversationId = {{ $conversation->id }};
        const userId = {{ auth()->id() }};
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const chat = document.getElementById('chat-body');
        const marker = document.getElementById('unread-marker');

        if (marker) {
            // Scroll langsung ke pembatas pesan baru
            chat.scrollTop = marker.offsetTop - 50;
            // Hilangkan pembatas setelah 1.5 detik
            setTimeout(() => marker.remove(), 1500);
        } else {
            // Tidak ada pesan baru → scroll ke bawah biasa
            chat.scrollTop = chat.scrollHeight;
        }
    });

    // Setelah kirim pesan, tetap scroll ke bawah
    document.querySelector('form[action*="send"]')?.addEventListener("submit", () => {
        setTimeout(() => {
            const chat = document.getElementById('chat-body');
            chat.scrollTop = chat.scrollHeight;
        }, 100);
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggle = document.getElementById('menuToggle');
        const menu = document.getElementById('menuDropdown');

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });

        // Biar dropdown tidak ketutup saat klik item menu
        menu.addEventListener('click', (e) => e.stopPropagation());

        // Klik di luar → tutup
        document.addEventListener('click', () => menu.classList.add('hidden'));

        // Aksi tombol:
        document.getElementById('viewProfile').addEventListener('click', () => {
            alert("Nanti diarahkan ke halaman profil si user.");
        });

        document.getElementById('deleteChat').addEventListener('click', () => {
            alert("Nanti diarahkan ke konfirmasi hapus percakapan.");
        });

        document.getElementById('blockUser').addEventListener('click', () => {
            alert("Nanti diarahkan ke fungsi blokir user.");
        });
    });
    </script>

    <script>
    document.addEventListener("input", () => {
        const input = document.getElementById('chatInput');
        input.style.height = "auto";
        input.style.height = (input.scrollHeight) + "px";
    });
    </script>
</x-app-layout>
