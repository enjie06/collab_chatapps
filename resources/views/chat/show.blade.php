<x-app-layout>
    @php
        $isGroup = $conversation->type === 'group';

        $otherUser = !$isGroup
            ? $conversation->users->firstWhere('id', '!=', auth()->id())
            : null;

        // Anggota grup
        $members = $isGroup
            ? $conversation->users->where('id', '!=', auth()->id())
            : collect();

        // Logika blokir
        if ($isGroup) {
            $friendship = null;
            $isBlocked = false;
            $isFriend = true; // grup selalu bisa kirim pesan
        } else {
            $friendship = \App\Models\Friendship::between(auth()->id(), $otherUser->id)->first();
            $isBlocked = $friendship && $friendship->is_blocked;
            $isFriend = $friendship && $friendship->status === 'accepted' && !$isBlocked;
        }
    @endphp  

    <div class="max-w-3xl mx-auto mt-2 flex flex-col h-[calc(100vh-110px)] space-y-2">

        <!-- Header Chat -->
        <div class="flex items-center justify-between bg-white border-b p-3 sticky top-0 z-10">

            <a href="{{ route('chat.index') }}" class="text-2xl text-rose-600 hover:text-rose-800 pr-2">←</a>

            {{-- === JIKA GRUP === --}}
            @if($isGroup)
                <div class="flex items-center gap-3 flex-1">
                    <img src="{{ $conversation->avatar ? asset('storage/'.$conversation->avatar) : asset('images/default-group.png') }}"
                        class="w-10 h-10 rounded-full object-cover border">

                    <div class="leading-tight">
                        <p class="font-semibold text-gray-800">{{ $conversation->name }}</p>

                        <p class="text-xs text-gray-500">
                            {{ $members->pluck('name')->implode(', ') }}
                        </p>
                    </div>
                </div>

            {{-- === JIKA PRIVATE === --}}
            @else
                <div class="flex items-center gap-3 flex-1">
                    <img src="{{ $otherUser?->avatar ? asset('storage/'.$otherUser->avatar) : asset('images/default-avatar.png') }}"
                        class="w-10 h-10 rounded-full object-cover border">

                    <div class="leading-tight">
                        <p class="font-semibold text-gray-800">{{ $otherUser->name }}</p>
                        <p class="text-xs {{ $otherUser->is_online ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $otherUser->is_online ? 'Online' : 'Offline' }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- MENU (Nanti bisa beda untuk grup) --}}
            <div class="relative">
                <button id="menuToggle" class="text-xl px-2 text-gray-600 hover:text-gray-800">⋮</button>

                <div id="menuDropdown"
                    class="hidden absolute right-0 mt-2 w-44 bg-white border rounded-lg shadow-lg p-3">

                    @if($isGroup)
                        <p class="font-semibold text-center">{{ $conversation->name }}</p>
                        <p class="text-xs text-gray-500 text-center mb-2">
                            {{ $members->pluck('name')->implode(', ') }}
                        </p>

                        <button class="block text-left w-full hover:text-rose-600 text-sm">
                            Kelola Grup (nanti)
                        </button>

                    @else
                        <p class="font-semibold text-center">{{ $otherUser->name }}</p>
                        <p class="text-xs text-gray-500 text-center mb-2">{{ $otherUser->email }}</p>

                        <button onclick="window.location.href='{{ route('user.profile', $otherUser->id) }}'"
                            class="block text-left w-full hover:text-rose-600 text-sm">
                            Lihat Profil
                        </button>                     

                        <form action="{{ route('chat.delete', $conversation->id) }}" 
                            method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="block w-full text-left hover:text-rose-600 text-sm">
                                Hapus Chat
                            </button>
                        </form>

                        @php
                            if ($isGroup) {
                                // grup tidak pakai blokir
                                $friendship = null;
                                $isBlocked = false;
                                $canBlock = false;
                                $canUnblock = false;
                            } else {
                                $friendship = \App\Models\Friendship::between(auth()->id(), $otherUser->id)->first();
                                $isBlocked = $friendship && $friendship->is_blocked;
                                $canBlock = !$isBlocked;
                                $canUnblock = $isBlocked && $friendship->blocked_by == auth()->id();
                            }
                        @endphp

                        @if($canBlock)
                            <form action="{{ route('friends.block', $otherUser->id) }}" method="POST">
                                @csrf
                                <button class="block w-full text-left hover:text-rose-600 text-sm">Blokir</button>
                            </form>
                        @endif

                        @if($canUnblock)
                            <form action="{{ route('friends.unblock', $otherUser->id) }}" method="POST">
                                @csrf
                                <button class="block w-full text-left hover:text-green-600 text-sm">Buka Blokir</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div id="typingIndicator" class="text-xs text-gray-500 px-3 py-1"></div>
        </div>

        <!-- Chat Messages -->
        <div class="bg-white p-4 rounded-lg border h-[200vh] overflow-y-auto" id="chat-body">
            @php $isUnreadBoundaryShown = false; @endphp

            @php $lastDate = null @endphp

            @foreach($messages as $message)
                @php
                    $currentDate = $message->created_at->format('d M Y');
                    $isMe = $message->user_id === auth()->id();
                @endphp

                <!-- Tampilkan tanggal jika beda dari pesan sebelumnya -->
                @if($lastDate !== $currentDate)
                    <div class="text-center my-4">
                        <span class="px-3 py-1 text-xs bg-gray-300 text-gray-700 rounded-full">
                            {{ $currentDate === now()->format('d M Y') ? 'Hari Ini' : $currentDate }}
                        </span>
                    </div>
                    @php $lastDate = $currentDate; @endphp
                @endif

                <!-- Tanda Pesan Baru -->
                @if(!$isUnreadBoundaryShown && $message->id > $lastRead && $message->user_id != auth()->id())
                    <div id="unread-marker" class="text-center my-3">
                        <span class="px-3 py-1 text-xs bg-rose-200 text-rose-700 rounded-full">
                            Pesan Baru
                        </span>
                    </div>
                    @php $isUnreadBoundaryShown = true; @endphp
                @endif

                <div class="mb-3 flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[70%]">

                        {{-- Tampilkan nama + avatar di grup --}}
                        @if($conversation->type === 'group' && !$isMe)
                            <div class="flex items-center gap-2 mb-1">
                                <img src="{{ $message->user->avatar ? asset('storage/'.$message->user->avatar) : asset('images/default-avatar.png') }}"
                                    class="w-6 h-6 rounded-full border object-cover">
                                <span class="text-xs font-medium text-gray-700">{{ $message->user->name }}</span>
                            </div>
                        @endif

                        {{-- BUBBLE --}}
                        <div class="px-3 py-2 rounded-xl leading-snug break-words
                            {{ $isMe ? 'bg-rose-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-900 rounded-bl-none' }}">

                            {{-- Lampiran --}}
                            @if($message->attachment)
                                @php $att = $message->attachment; @endphp

                                @if(str_contains($att->file_type, 'image'))
                                    <img src="{{ asset('storage/'.$att->file_path) }}" class="rounded-lg max-w-full mb-2">
                                @endif

                                @if($att->file_type === 'video')
                                    <video controls class="rounded-lg max-w-full mb-2">
                                        <source src="{{ asset('storage/'.$att->file_path) }}">
                                    </video>
                                @endif

                                @if($att->file_type === 'audio')
                                    <audio controls class="w-full mb-2">
                                        <source src="{{ asset('storage/'.$att->file_path) }}">
                                    </audio>
                                @endif

                                @if($att->file_type === 'file')
                                    <a href="{{ asset('storage/'.$att->file_path) }}"
                                        class="text-blue-600 underline block mb-2">📄 Download File</a>
                                @endif
                            @endif

                            {!! nl2br(e($message->content)) !!}

                            <div class="text-[10px] opacity-70 mt-1 text-right">
                                {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Form kirim pesan -->
        @if($isFriend)
            {{-- FORM KIRIM PESAN --}}
            <form action="{{ route('chat.send', $conversation->id) }}" method="POST" 
                enctype="multipart/form-data"
                class="flex items-center gap-2 p-2 border-t bg-white sticky bottom-0">
                @csrf
                <textarea name="content" id="chatInput"
                    class="flex-1 border rounded-lg px-2 py-1 focus:border-rose-500 resize-none overflow-y-auto text-[13px] h-[40px]"
                    placeholder="Tulis pesan..." required></textarea>

                <label class="cursor-pointer bg-gray-200 w-[40px] h-[40px] 
                            rounded-lg hover:bg-gray-300 text-lg flex items-center justify-center">
                    📎
                    <input type="file" name="attachment" class="hidden"
                        accept="image/*,video/*,.pdf,.doc,.docx,.zip,.mp3,.wav,.m4a">
                </label>

                <label class="cursor-pointer bg-gray-200 w-[40px] h-[40px] 
                            rounded-lg hover:bg-gray-300 text-lg flex items-center justify-center">
                    🎤
                    <input type="file" name="voice_note" class="hidden" accept="audio/*">
                </label>

                <button class="bg-rose-600 text-white px-4 h-[40px] rounded-lg 
                            hover:bg-rose-700 text-[14px] flex items-center">
                    Kirim
                </button>
            </form>
        @else
            <div class="text-center text-gray-500 text-sm italic py-3 sticky bottom-0 bg-white border-t">
                @if($isBlocked)
                    Kalian tidak dapat saling mengirim pesan.
                @elseif($friendship && $friendship->status !== 'accepted')
                    Kalian sudah tidak berteman. Chat hanya dapat dibaca.
                @else
                    Chat tidak bisa digunakan.
                @endif
            </div>
        @endif
    </div>

    <script>
        window.conversationId = {{ $conversation->id }};
        window.userId = {{ auth()->id() }};
        window.userName = "{{ auth()->user()->name }}";
        window.isGroup = {{ $isGroup ? 'true' : 'false' }};

    // Pembatas pesan baru
    document.addEventListener("DOMContentLoaded", () => {
        const chat = document.getElementById('chat-body');
        const marker = document.getElementById('unread-marker');

        if (marker) {
            // Scroll langsung ke pembatas pesan baru
            chat.scrollTo({
                top: marker.offsetTop - 150,
                behavior: 'smooth'
            });
            // Hilangkan pembatas setelah 2 detik
            setTimeout(() => marker.remove(), 2000);
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
        }, 50);
    });

    // Titik tiga
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

    // Merapikan textarea input pesan
    document.addEventListener("DOMContentLoaded", () => {
        const textarea = document.getElementById("chatInput");
        const chat = document.getElementById("chat-body");
        const maxHeight = 150; // tinggi maksimal textarea (opsional)

        textarea.addEventListener("input", () => {
            // Resize otomatis
            textarea.style.height = "auto";
            textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + "px";

            // Selalu scroll ke bawah setelah tinggi textarea berubah
            setTimeout(() => {
                chat.scrollTop = chat.scrollHeight;
            }, 10);
        });
    });
    </script>
</x-app-layout>
