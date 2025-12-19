<x-iframe-layout>
    <style>
        /* Image thumbnail hover effect */
        .img-thumbnail {
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .img-thumbnail:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Chat Input */
        .chatInput {
            min-height: 45px;
            max-height: 120px;
            resize: none;
            width: 100%;
            border-radius: 9999px;
            padding: 10px 14px;
            line-height: 20px;
        }

        .mention {
            color: #2563eb;
            font-weight: 600;
        }

        /* Modal animation */
        #imageModal {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive image in chat */
        @media (max-width: 640px) {
            .chat-image img {
                max-width: 150px !important;
                max-height: 150px !important;
            }
        }
    </style>
    @php
        $isGroup = $conversation->type === 'group';
        $isBroadcast = $conversation->type === 'broadcast';

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
            $isLeft = false;

            if ($isGroup) {
                $pivot = $conversation->users->firstWhere('id', auth()->id())?->pivot;
                $isLeft = !is_null($pivot?->deleted_at);
                $isFriend = !$isLeft; // tidak boleh kirim chat jika sudah keluar
            }
        } else {
            $friendship = \App\Models\Friendship::between(auth()->id(), $otherUser->id)->first();
            $isBlocked = $friendship && $friendship->is_blocked;
            $isFriend = $friendship && $friendship->status === 'accepted' && !$isBlocked;
        }
    @endphp 
    
    @if(session('error_admin_leave'))
        <div id="adminWarning"
            class="mx-4 my-2 text-center text-xs bg-rose-100 text-rose-700 py-2 rounded-lg">
            {{ session('error_admin_leave') }}
        </div>

        <script>
            setTimeout(() => {
                document.getElementById('adminWarning')?.remove();
            }, 2500);
        </script>
    @endif

<div class="w-full h-screen flex flex-col">
        <!-- Header Chat -->
        <!-- Header Chat -->
<div class="flex items-center justify-between bg-white border-b p-3 sticky top-0 z-10">

    {{-- GANTI TOMBOL INI SAJA: --}}
    <button onclick="closeChatOnly()" class="text-2xl text-rose-600 hover:text-rose-800 pr-2">←</button>
    {{-- HAPUS: <a href="{{ route('chat.index') }}" class="text-2xl text-rose-600 hover:text-rose-800 pr-2">←</a> --}}

    {{-- === JIKA GRUP / BROADCAST === --}}
    @if($isGroup || $isBroadcast)
    <div class="flex items-center gap-3 flex-1">
        <img src="{{ $conversation->avatar
            ? asset('storage/'.$conversation->avatar)
            : asset('images/default-group.png') }}"
            class="w-10 h-10 rounded-full object-cover border">

        <div class="leading-tight">
            <p class="font-semibold text-gray-800">
                {{ $conversation->name }}
            </p>

            @php
                $activeMembers = $conversation->users->filter(
                    fn($u) => is_null($u->pivot->deleted_at)
                );

                $names = $activeMembers->map(fn($u) =>
                    $u->id === auth()->id()
                        ? $u->name.' (You)'
                        : $u->name
                );

                $namesSorted = $names->sort(fn($a, $b) =>
                    str_contains($a, '(You)') <=> str_contains($b, '(You)')
                );
            @endphp

            <p class="text-xs text-gray-500">
                {{ $namesSorted->implode(', ') }}
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

    {{-- MENU --}}
    <div class="relative">
        <button id="menuToggle" class="text-xl px-2 text-gray-600 hover:text-gray-800">⋮</button>

        <div id="menuDropdown"
            class="hidden absolute right-0 mt-2 w-44 bg-white border rounded-lg shadow-lg p-3">

            @if($isGroup)
                <p class="font-semibold text-center">{{ $conversation->name }}</p>

                <button
                    onclick="window.location.href='{{ route('group.info', $conversation->id) }}'"
                    class="block w-full text-left hover:text-rose-600 text-sm">
                    Kelola Grup
                </button>

                {{-- Hapus Chat (hanya hapus chat milik kita) --}}
                <form action="{{ route('chat.delete', $conversation->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button class="block w-full text-left hover:text-rose-600 text-sm">
                        Hapus Chat
                    </button>
                </form>

                {{-- Leave Group --}}
                <form action="{{ route('group.leave', $conversation->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="block w-full text-left hover:text-red-600 text-sm">
                        Tinggalkan Grup
                    </button>
                </form>
                
            @elseif($isBroadcast)
                <button
                    onclick="window.location.href='{{ route('broadcast.info', $conversation->id) }}'"
                    class="block w-full text-left hover:text-rose-600 text-sm">
                    Kelola Broadcast
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

{{-- TAMBAHKAN SCRIPT INI DI BAWAH (sebelum </x-app-layout>) --}}
<script>
function closeChatOnly() {
    // Jika di dalam iframe (dipakai di layout split)
    if (window.parent !== window) {
        // Coba beri tahu parent untuk menutup chat
        try {
            const parentDoc = window.parent.document;
            
            // Sembunyikan iframe
            const iframe = parentDoc.getElementById('chatFrame');
            if (iframe) {
                iframe.classList.add('hidden');
            }
            
            // Tampilkan empty state
            const emptyState = parentDoc.getElementById('emptyState');
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }
            
            // Hapus status aktif dari semua chat di sidebar
            parentDoc.querySelectorAll('.chat-room-item').forEach(item => {
                item.classList.remove('active');
            });
        } catch (e) {
            console.log('Tidak bisa mengakses parent, redirect ke chat index');
            window.location.href = "{{ route('chat.index') }}";
        }
    } else {
        // Jika standalone (mobile/direct access), redirect ke chat index
        window.location.href = "{{ route('chat.index') }}";
    }
}
</script>

        <!-- Chat Messages -->
        <div class="bg-white p-4 rounded-lg border h-[200vh] overflow-y-auto" id="chat-body">
            @php $isUnreadBoundaryShown = false; @endphp

            @php $lastDate = null @endphp

            @foreach($messages as $message)

                {{-- SYSTEM MESSAGE --}}
                @if(Str::startsWith($message->content, '[SYSTEM]'))
                    <div class="text-center text-xs text-gray-500 my-2">
                        {{ Str::after($message->content, '[SYSTEM] ') }}
                    </div>
                    @continue
                @endif

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

                            {{-- TAMPILKAN REPLY JIKA ADA --}}
                            @php $reply = $message->replyTo; @endphp
                            @if($reply)
                                <div class="mb-2 p-2 bg-{{ $isMe ? 'rose-500' : 'gray-300' }} rounded-lg border-l-4 border-{{ $isMe ? 'rose-300' : 'gray-400' }}">
                                    <p class="text-xs font-semibold text-{{ $isMe ? 'rose-100' : 'gray-600' }}">
                                        Membalas: {{ $message->replyTo->user->name }}
                                    </p>
                                    <p class="text-sm text-{{ $isMe ? 'rose-50' : 'gray-700' }} truncate">
                                        {{ $reply->content ?: '[File]' }}
                                    </p>
                                </div>
                            @endif

                            {{-- Lampiran --}}
                            @if($message->attachment)
                                @php $att = $message->attachment; @endphp

                                {{-- IMAGE --}}
                                @if(str_contains($att->file_type, 'image'))
                                    <div class="mb-2 space-y-1">

                                        {{-- FOTO --}}
                                        <a href="#"
                                            onclick="showImageModal(
                                                '{{ asset('storage/'.$att->file_path) }}',
                                                '{{ $message->user->name }}',
                                                '{{ $message->created_at->format('d M Y H:i') }}'
                                            )"
                                            class="block chat-image">
                                            <img src="{{ asset('storage/'.$att->file_path) }}"
                                                class="rounded-lg max-w-[200px] max-h-[200px] object-cover border img-thumbnail">
                                        </a>

                                        {{-- DOWNLOAD BUTTON --}}
                                        <a href="{{ asset('storage/'.$att->file_path) }}"
                                        download
                                        class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v12m0 0l4-4m-4 4l-4-4M4 20h16"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>

                                {{-- NON IMAGE --}}
                                @else
                                    <div class="mb-1 p-3 rounded-lg
                                        {{ $isMe ? 'bg-rose-500 text-white' : 'bg-gray-300 text-gray-900' }}">
                                        <div class="flex items-center gap-3">
                                            <div class="text-2xl">📄</div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">
                                                    {{ $att->original_name }}
                                                </p>

                                                <a href="{{ route('chat.download', ['attachment' => $att->id]) }}"
                                                class="text-xs underline opacity-90">
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {!! nl2br(renderMentions($message->content, auth()->user())) !!}

                            <div class="text-[10px] opacity-70 mt-1 text-right">
                                {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>

                        {{-- BUTTON REPLY --}}
                        @if($canSend)
                        <div class="flex justify-{{ $isMe ? 'end' : 'start' }} mt-1">
                            <button onclick="replyToMessage({{ $message->id }}, '{{ $message->user->name }}', `{{ $message->content ?: '[File]' }}`)" 
                                    class="text-xs text-gray-500 hover:text-rose-600 px-2 py-1">
                                🔄 Balas
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Keluar grup -->
                @if($message->user_id == 0)
                    <div class="text-center text-xs text-gray-500 my-2">
                        {{ Str::after($message->content, '[SYSTEM] ') }}
                    </div>
                @else
                    {{-- tampilkan pesan normal --}}
                @endif
            @endforeach
        </div>

        <!-- Form kirim pesan -->
        @php
            $pivot = $conversation->users->firstWhere('id', auth()->id())?->pivot;
            $isLeft = $isGroup && !is_null($pivot?->deleted_at);
        @endphp

        @if($isGroup)
            {{-- ==== GROUP CHAT ==== --}}
            @if(!$isLeft)
                {{-- Masih anggota → boleh kirim pesan --}}
                <form action="{{ route('chat.send', $conversation->id) }}" method="POST" 
                    enctype="multipart/form-data"
                    class="relative flex items-center gap-2 p-2 border-t bg-white sticky bottom-0">
                    @csrf

                    <!-- TAMBAH INI DI DALAM FORM -->
                    <!-- HIDDEN INPUT UNTUK REPLY -->
                    <input type="hidden" name="reply_to_id" id="replyToId">

                    <!-- REPLY PREVIEW -->
                    <div id="replyPreview" class="hidden mb-2 p-2 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-xs text-blue-600 font-semibold">Membalas pesan</p>
                                <p id="replyContent" class="text-sm text-gray-700 truncate"></p>
                            </div>
                            <button type="button" onclick="cancelReply()" class="text-gray-500 hover:text-gray-700">×</button>
                        </div>
                    </div>
                    <!-- SAMPAI SINI -->

                    <!-- PREVIEW FILE (Seperti WhatsApp) -->
                    <div id="filePreview" class="hidden mb-2 p-3 bg-gray-100 rounded-lg border">
                        <div class="flex items-center justify-between">
                            <div>
                                <span id="fileName" class="text-sm font-medium text-gray-700"></span>
                                <p id="fileSize" class="text-xs text-gray-500"></p>
                            </div>
                            <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700 text-lg">×</button>
                        </div>
                        
                        <!-- Preview Gambar -->
                        <img id="imagePreview" class="hidden max-w-xs mt-2 rounded-lg shadow">
                        
                        <!-- Preview Video -->
                        <video id="videoPreview" class="hidden max-w-xs mt-2 rounded-lg shadow" controls></video>
                        
                        <!-- Untuk file lainnya -->
                        <div id="otherFilePreview" class="hidden mt-2 text-center">
                            <div class="text-4xl">📄</div>
                            <p class="text-xs text-gray-600 mt-1">File siap dikirim</p>
                        </div>
                    </div>

                    <div class="relative flex-1">
                        <textarea name="content"
                            class="chatInput w-full border rounded-full px-4 py-2 text-sm resize-none"
                            placeholder="Tulis pesan..."
                            required></textarea>

                        <div id="mentionBox"
                            class="hidden absolute left-0 bottom-full mb-1 w-72 bg-white border rounded-lg shadow-md max-h-40 overflow-y-auto text-sm z-50">
                        </div>
                    </div>

                    <label class="cursor-pointer bg-gray-200 w-[40px] h-[40px] 
                                rounded-lg hover:bg-gray-300 text-lg flex items-center justify-center">
                        📎
                        <input type="file" name="attachment" id="fileInput" class="hidden"
                            accept="image/*,video/*,.pdf,.doc,.docx,.zip,.mp3,.wav,.m4a"
                            onchange="previewFile(this)">
                    </label>

                    <button class="bg-rose-600 text-white px-4 h-[40px] rounded-lg 
                                hover:bg-rose-700 text-[14px] flex items-center">
                        Kirim
                    </button>
                </form>
            @else
                {{-- Sudah keluar grup --}}
                <div class="text-center text-gray-500 text-sm italic py-3 sticky bottom-0 bg-white border-t">
                    Kamu telah keluar dari grup ini. Chat hanya dapat dibaca.
                </div>
            @endif

        @else
            {{-- ==== PRIVATE CHAT ==== --}}
            @if($isFriend)
                {{-- Bisa kirim pesan --}}
                <form class="flex items-end gap-2 p-2 border-t bg-white sticky bottom-0"
                    action="{{ route('chat.send', $conversation->id) }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- TAMBAH INI DI DALAM FORM -->
                    <!-- HIDDEN INPUT UNTUK REPLY -->
                    <input type="hidden" name="reply_to_id" id="replyToId">

                    <!-- REPLY PREVIEW -->
                    <div id="replyPreview" class="hidden mb-2 p-2 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-xs text-blue-600 font-semibold">Membalas pesan</p>
                                <p id="replyContent" class="text-sm text-gray-700 truncate"></p>
                            </div>
                            <button type="button" onclick="cancelReply()" class="text-gray-500 hover:text-gray-700">×</button>
                        </div>
                    </div>
                    <!-- SAMPAI SINI -->

                    <!-- PREVIEW FILE (Seperti WhatsApp) -->
                    <div id="filePreview" class="hidden mb-2 p-3 bg-gray-100 rounded-lg border">
                        <div class="flex items-center justify-between">
                            <div>
                                <span id="fileName" class="text-sm font-medium text-gray-700"></span>
                                <p id="fileSize" class="text-xs text-gray-500"></p>
                            </div>
                            <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700 text-lg">×</button>
                        </div>
                        
                        <!-- Preview Gambar -->
                        <img id="imagePreview" class="hidden max-w-xs mt-2 rounded-lg shadow">
                        
                        <!-- Preview Video -->
                        <video id="videoPreview" class="hidden max-w-xs mt-2 rounded-lg shadow" controls></video>
                        
                        <!-- Untuk file lainnya -->
                        <div id="otherFilePreview" class="hidden mt-2 text-center">
                            <div class="text-4xl">📄</div>
                            <p class="text-xs text-gray-600 mt-1">File siap dikirim</p>
                        </div>
                    </div>

                    <textarea name="content" class="chatInput"
                        class="flex-1 border rounded-lg px-2 py-1 focus:border-rose-500 resize-none overflow-y-auto text-[13px] h-[40px]"
                        placeholder="Tulis pesan..." required></textarea>

                    <label class="cursor-pointer bg-gray-200 w-[40px] h-[40px] 
                                rounded-lg hover:bg-gray-300 text-lg flex items-center justify-center">
                        📎
                        <input type="file" name="attachment" id="fileInput" class="hidden"
                            accept="image/*,video/*,.pdf,.doc,.docx,.zip,.mp3,.wav,.m4a"
                            onchange="previewFile(this)">
                    </label>

                    <button class="bg-rose-600 text-white px-4 h-[40px] rounded-lg 
                                hover:bg-rose-700 text-[14px] flex items-center">
                        Kirim
                    </button>
                </form>
            @else
                {{-- ==== BROADCAST ==== --}}
                @if($isBroadcast)

                    @php
                        $pivot = $conversation->users
                            ->firstWhere('id', auth()->id())?->pivot;

                        $isRemoved = !is_null($pivot?->deleted_at);
                        $isAdmin   = $pivot?->role === 'admin';
                    @endphp

                    @if($isAdmin)
                        {{-- Admin boleh kirim --}}
                        {{-- (form kirim pesan tetap di sini) --}}

                    @else
                        {{-- Tidak bisa kirim --}}
                        <div class="text-center text-gray-500 text-sm italic py-3 sticky bottom-0 bg-white border-t">
                            @if($isRemoved)
                                Kamu bukan lagi member broadcast.
                            @else
                                Broadcast bersifat satu arah. Kamu hanya bisa membaca pesan.
                            @endif
                        </div>
                    @endif
                @endif
            @endif
        @endif
    </div>

    <!-- MODAL UNTUK PREVIEW GAMBAR BESAR -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex-col items-center justify-center p-4">
        <!-- Tombol tutup -->
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
            ✕
        </button>
        
        <!-- Tombol download di modal -->
        <a id="modalDownloadBtn" 
        class="absolute top-4 left-4 text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg flex items-center gap-2 z-10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v-6m0 0l-3 3m3-3l3 3M3 21h18"/>
            </svg>
            Download
        </a>
        
        <!-- Gambar -->
        <div class="max-w-4xl max-h-[80vh] flex items-center justify-center">
            <img id="modalImage" src="" 
                class="max-w-full max-h-[80vh] object-contain rounded-lg"
                onclick="closeImageModal()">
        </div>
        
        <!-- Info gambar -->
        <div class="mt-4 text-white text-center">
            <p id="imageSender" class="font-medium"></p>
            <p id="imageTime" class="text-sm text-gray-300"></p>
            <p id="imageSize" class="text-sm text-gray-300"></p>
        </div>
    </div>

    @php
        $groupMembers = $conversation->users
            ->whereNull('pivot.deleted_at')
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username ?? Str::slug($u->name),
            ])
            ->values();
    @endphp

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
    });

    // Merapikan textarea input pesan
    document.addEventListener("DOMContentLoaded", () => {
        const maxHeight = 90; // maksimal 2–3 baris

        document.querySelectorAll('.chatInput').forEach(textarea => {
            textarea.style.height = '40px';

            textarea.addEventListener('input', () => {
                textarea.style.height = '40px';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            });
        });
    });

    // === FUNGSI PREVIEW FILE ===
    function previewFile(input) {
        const file = input.files[0];
        if (!file) return;

        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const imagePreview = document.getElementById('imagePreview');
        const videoPreview = document.getElementById('videoPreview');
        const otherFilePreview = document.getElementById('otherFilePreview');
        const textarea = document.querySelector('.chatInput');

        // Reset semua preview
        imagePreview.classList.add('hidden');
        videoPreview.classList.add('hidden');
        otherFilePreview.classList.add('hidden');
        
        // Tampilkan info file
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        preview.classList.remove('hidden');

        // OTOMATIS ISI TEXTAREA DENGAN NAMA FILE
        // Ini yang bikin required terpenuhi!
        if (!textarea.value.trim()) {
            textarea.value = `Mengirim file: ${file.name}`;
        }

        // Preview berdasarkan tipe file
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
        else if (file.type.startsWith('video/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                videoPreview.src = e.target.result;
                videoPreview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
        else {
            // Untuk file lainnya (PDF, DOC, dll)
            otherFilePreview.classList.remove('hidden');
        }
    }

    // Format ukuran file
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Hapus file preview
    function clearFile() {
        const textarea = document.querySelector('.chatInput');
        
        // HAPUS TEXT OTOMATIS JIKA USER HAPUS FILE
        if (textarea.value.startsWith('Mengirim file:')) {
            textarea.value = '';
        }
        
        document.getElementById('fileInput').value = '';
        document.getElementById('filePreview').classList.add('hidden');
    }

    // === VALIDASI FORM TAMBAHAN ===
    document.querySelector('form[action*="send"]')?.addEventListener("submit", function(e) {
        const textarea = this.querySelector('textarea[name="content"]');
        const file = this.querySelector('input[name="attachment"]').files[0];
        
        // JIKA ADA FILE TAPI TEXTAREA MASIH KOSONG, OTOMATIS ISI LAGI (double safety)
        if (file && !textarea.value.trim()) {
            textarea.value = `Mengirim file: ${file.name}`;
        }
        
        // Biarkan HTML5 validation yang handle required
        // Tidak perlu e.preventDefault(), biarkan form validation normal
    });

    // === FUNGSI UNTUK MODAL GAMBAR ===
    function showImageModal(imageSrc, senderName, sentTime, fileSize = '') {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const imageSender = document.getElementById('imageSender');
        const imageTime = document.getElementById('imageTime');
        const imageSize = document.getElementById('imageSize');
        const downloadBtn = document.getElementById('modalDownloadBtn');
        
        // Set gambar dan info
        modalImage.src = imageSrc;
        imageSender.textContent = `Dikirim oleh: ${senderName}`;
        imageTime.textContent = `Waktu: ${sentTime}`;
        imageSize.textContent = fileSize ? `Ukuran: ${fileSize}` : '';
        
        // Set download link
        downloadBtn.href = imageSrc;
        downloadBtn.download = senderName + '_' + Date.now() + '.jpg';
        
        // Tampilkan modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    // Tutup modal dengan ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });

    // Format file size helper (jika belum ada)
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // === FUNGSI REPLY MESSAGE ===
    function replyToMessage(messageId, userName, messageContent) {
        // Set hidden input
        document.getElementById('replyToId').value = messageId;
        
        // Tampilkan preview
        document.getElementById('replyContent').textContent = messageContent;
        document.getElementById('replyPreview').classList.remove('hidden');
        
        // Scroll ke form
        document.getElementById('chatInput').focus();
        document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
    }

    // Batalkan reply
    function cancelReply() {
        document.getElementById('replyToId').value = '';
        document.getElementById('replyPreview').classList.add('hidden');
    }

    // Jika user mulai ngetik, jangan batalkan reply otomatis
    document.getElementById('chatInput')?.addEventListener('input', function() {
        // Biarkan reply tetap aktif saat user ngetik
    });

    // Reset reply ketika form terkirim
    document.querySelector('form[action*="send"]')?.addEventListener("submit", function() {
        setTimeout(() => {
            cancelReply();
        }, 1000);
    });
</script>

<script>
    window.groupMembers = @json($groupMembers);

    document.querySelectorAll('.chatInput').forEach(textarea => {
        const mentionBox = textarea
            .closest('.relative')
            .querySelector('#mentionBox');

        textarea.addEventListener('input', () => {
            const cursorPos = textarea.selectionStart;
            const textBefore = textarea.value.slice(0, cursorPos);

            // detect @keyword
            const match = textBefore.match(/@([^\s@]*)$/);
            if (!match) {
                mentionBox.classList.add('hidden');
                return;
            }

            const keyword = match[1].toLowerCase();

            const results = window.groupMembers.filter(u =>
                u.name.toLowerCase().includes(keyword) ||
                u.username.toLowerCase().includes(keyword)
            );

            mentionBox.innerHTML = '';

            if (!results.length) {
                mentionBox.classList.add('hidden');
                return;
            }

            results.slice(0, 5).forEach((user, index) => {

                // divider (kecuali item pertama)
                if (index > 0) {
                    const divider = document.createElement('div');
                    divider.className = 'h-px bg-gray-100 mx-2';
                    mentionBox.appendChild(divider);
                }

                const item = document.createElement('div');
                item.className =
                    'px-3 py-2 cursor-pointer flex items-center rounded-md ' +
                    'hover:bg-blue-50 transition';

                item.innerHTML = `
                    <div class="flex items-center justify-between gap-3 w-full">
                        <span class="text-sm text-gray-800 truncate">
                            ${user.name}
                        </span>
                        <span class="text-xs text-gray-400 shrink-0">
                            @${user.username}
                        </span>
                    </div>
                `;

                item.onclick = () => {
                    const start = textarea.value.lastIndexOf(match[0]);
                    textarea.value =
                        textarea.value.substring(0, start) +
                        '@' + user.username + ' ' +
                        textarea.value.substring(cursorPos);

                    mentionBox.classList.add('hidden');
                    textarea.focus();
                };

                mentionBox.appendChild(item);
            });

            mentionBox.classList.remove('hidden');
        });

        // klik di luar → tutup
        document.addEventListener('click', e => {
            if (!textarea.contains(e.target) && !mentionBox.contains(e.target)) {
                mentionBox.classList.add('hidden');
            }
        });
    });
</script>

</x-iframe-layout>
