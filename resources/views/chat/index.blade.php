@php use Illuminate\Support\Str; @endphp

<x-app-layout>

    <div class="h-[calc(100vh-4rem)] flex bg-gradient-to-br from-rose-50 via-pink-50 to-purple-50">
        
        {{-- SIDEBAR KIRI --}}
        <div class="w-full md:w-[420px] bg-white/80 backdrop-blur-xl border-r border-rose-100 flex flex-col shadow-xl">
            
           {{-- Header Cute --}}
<div class="p-6 bg-gradient-to-br from-rose-50 via-pink-50 to-purple-50">
    <div class="relative">
        <input id="chatSearch" type="text" placeholder="🔍 Cari chat kamu..."
            class="w-full pl-12 pr-4 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm placeholder-gray-400 focus:ring-4 focus:ring-rose-100 focus:border-rose-300 shadow-sm">
        <div class="absolute left-4 top-1/2 -translate-y-1/2">
            <div class="w-8 h-8 bg-gradient-to-br from-rose-200 to-pink-200 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

            {{-- Cute Tabs --}}
            <div class="flex gap-2 p-4 bg-white/50">
                <button onclick="showTab('chats')" class="tab-btn flex-1 py-3 px-4 text-sm font-bold text-white bg-gradient-to-r from-rose-400 to-pink-400 rounded-2xl shadow-lg transform transition hover:scale-105" data-tab="chats">
                    💬 Chats
                </button>
                <button onclick="showTab('groups')" class="tab-btn flex-1 py-3 px-4 text-sm font-semibold text-gray-600 bg-gray-100 rounded-2xl transition hover:bg-gray-200" data-tab="groups">
                    👥 Groups
                </button>
                <button onclick="showTab('contacts')" class="tab-btn flex-1 py-3 px-4 text-sm font-semibold text-gray-600 bg-gray-100 rounded-2xl transition hover:bg-gray-200" data-tab="contacts">
                    📱 Contacts
                </button>
            </div>

            {{-- Flash Notifications --}}
            @if(session('success'))
                <div class="flash mx-4 mt-2 bg-gradient-to-r from-green-400 to-emerald-400 text-white px-4 py-3 rounded-2xl shadow-lg flex items-center gap-3 animate-bounce-slow">
                    <span class="text-2xl">✨</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="flash mx-4 mt-2 bg-gradient-to-r from-red-400 to-rose-400 text-white px-4 py-3 rounded-2xl shadow-lg flex items-center gap-3 animate-bounce-slow">
                    <span class="text-2xl">⚠️</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            {{-- Content Area --}}
            <div class="flex-1 overflow-y-auto">
                
                {{-- TAB: CHATS --}}
                <div id="tab-chats" class="tab-content p-4 space-y-3">
                    @forelse($conversations as $conversation)
                        @php
                            $isGroup = $conversation->type === 'group';
                            $isBroadcast = $conversation->type === 'broadcast';
                            $isPrivate = $conversation->type === 'private';
                            $lastMsg = $conversation->last_visible_message;
                            $myRead = $conversation->users->firstWhere('id', auth()->id())?->pivot?->last_read_message_id ?? 0;
                            $hasUnread = $lastMsg && $lastMsg->user_id != auth()->id() && $myRead < $lastMsg->id;
                            $partner = $isPrivate ? $conversation->users->firstWhere('id', '!=', auth()->id()) : null;
                        @endphp

                        <a href="{{ route('chat.show', $conversation->id) }}" 
                           target="chatFrame"
                           data-chat-item
                           onclick="setActiveChat(this, {{ $conversation->id }})"
                            class="chat-room-item block p-4 bg-white rounded-3xl hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border border-rose-100 relative overflow-hidden group"
                            data-conversation-id="{{ $conversation->id }}">
                            
                            {{-- Gradient Background on Hover --}}
                            <div class="absolute inset-0 bg-gradient-to-r from-rose-50 to-pink-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <div class="relative flex items-center gap-4">
                                {{-- Avatar with Ring --}}
                                <div class="relative flex-shrink-0">
                                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-rose-400 to-pink-500 p-0.5 shadow-lg">
                                        <img src="{{ 
                                            ($isGroup || $isBroadcast)
                                                ? ($conversation->avatar ? asset('storage/'.$conversation->avatar) : asset('images/default-group.png'))
                                                : ($partner?->avatar ? asset('storage/'.$partner->avatar) : asset('images/default-avatar.png'))
                                        }}" class="w-full h-full rounded-full object-cover bg-white">
                                    </div>
                                    
                                    @if($isPrivate && $partner?->is_online)
                                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-gradient-to-br from-green-400 to-emerald-500 border-3 border-white rounded-full shadow-lg animate-pulse"></div>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-bold text-gray-900 truncate text-base">
                                            @if($isGroup || $isBroadcast)
                                                {{ $conversation->name }}
                                            @else
                                                {{ $partner?->name ?? 'User tidak ditemukan' }}
                                            @endif
                                        </h4>
                                        @if($lastMsg)
                                            <span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                                                {{ $lastMsg->created_at->diffForHumans(null, true, true) }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm text-gray-500 truncate flex-1">
                                            {{ $lastMsg ? Str::limit($lastMsg->content, 30) : '💭 Mulai chat sekarang!' }}
                                        </p>
                                        @if($hasUnread)
                                            <div class="flex-shrink-0 w-6 h-6 bg-gradient-to-br from-rose-500 to-pink-500 text-white text-xs rounded-full flex items-center justify-center font-bold shadow-lg animate-bounce">
                                                1
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-12 text-center">
                            <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-rose-100 to-pink-100 rounded-3xl flex items-center justify-center shadow-lg transform rotate-3">
                                <span class="text-6xl transform -rotate-3">💬</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">Belum ada chat nih! 🥺</h3>
                            <p class="text-sm text-gray-500">Yuk mulai chat dengan teman-temanmu</p>
                        </div>
                    @endforelse
                </div>

                {{-- TAB: GROUPS --}}
                <div id="tab-groups" class="tab-content hidden p-4 space-y-4">
                    {{-- Create Button --}}
                    <button onclick="window.location.href='{{ route('group.create') }}'"
                        class="w-full py-4 bg-gradient-to-r from-rose-400 via-pink-400 to-purple-400 text-white rounded-2xl hover:shadow-xl transition-all duration-300 hover:-translate-y-1 font-bold text-sm flex items-center justify-center gap-2 shadow-lg">
                        <span class="text-xl">✨</span>
                        <span>Buat Grup Baru</span>
                    </button>

                    {{-- Broadcast Section --}}
                    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-4 border border-yellow-200">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">📢</span>
                                <h3 class="font-bold text-gray-800">Broadcast</h3>
                            </div>
                            <a href="{{ route('broadcast.create') }}" 
                                class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-400 rounded-full flex items-center justify-center text-white shadow-lg hover:shadow-xl transition-all hover:scale-110">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                            </a>
                        </div>

                        @foreach($conversations->where('type', 'broadcast')->filter(fn($g) => is_null($g->users->firstWhere('id', auth()->id())?->pivot?->deleted_at)) as $broadcast)
                            <a href="{{ route('chat.show', $broadcast->id) }}" 
                               target="chatFrame"
                               onclick="setActiveChat(this, {{ $broadcast->id }})"
                                class="flex items-center gap-3 p-3 bg-white rounded-xl hover:shadow-md transition mb-2">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-orange-400 p-0.5">
                                    <img src="{{ $broadcast->avatar ? asset('storage/'.$broadcast->avatar) : asset('images/default-group.png') }}"
                                        class="w-full h-full rounded-full object-cover bg-white">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-800 truncate text-sm">{{ $broadcast->name }}</h4>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ $broadcast->messages->count() ? Str::limit($broadcast->messages->last()->content, 30) : 'Belum ada pesan' }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Groups Section --}}
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">👥</span>
                            <h3 class="font-bold text-gray-800">Groups</h3>
                        </div>

                        @foreach($conversations->where('type', 'group')->filter(fn($g) => is_null($g->users->firstWhere('id', auth()->id())?->pivot?->deleted_at)) as $group)
                            <a href="{{ route('chat.show', $group->id) }}" 
                               target="chatFrame"
                               onclick="setActiveChat(this, {{ $group->id }})"
                                class="flex items-center gap-3 p-3 bg-white rounded-xl hover:shadow-md transition mb-2">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-400 p-0.5">
                                    <img src="{{ $group->avatar ? asset('storage/'.$group->avatar) : asset('images/default-group.png') }}"
                                        class="w-full h-full rounded-full object-cover bg-white">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-800 truncate text-sm">{{ $group->name }}</h4>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ $group->messages->count() ? Str::limit($group->messages->last()->content, 30) : 'Belum ada pesan' }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- TAB: CONTACTS --}}
                <div id="tab-contacts" class="tab-content hidden p-4 space-y-4">
                    
                    {{-- Friend Requests --}}
                    @if($incoming->count())
                        <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-2xl p-4 border-2 border-rose-200 shadow-lg">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-2xl animate-bounce">🎉</span>
                                <h3 class="font-bold text-rose-600">Permintaan Masuk</h3>
                                <span class="ml-auto w-6 h-6 bg-rose-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                    {{ $incoming->count() }}
                                </span>
                            </div>
                            @foreach($incoming as $req)
                                <div class="flex items-center justify-between p-3 bg-white rounded-xl mb-2 shadow-sm">
                                    <span class="font-semibold text-gray-900">{{ $req->requester->name }}</span>
                                    <div class="flex gap-2">
                                        <form action="{{ route('friends.accept', $req->id) }}" method="POST">
                                            @csrf
                                            <button class="px-4 py-2 bg-gradient-to-r from-green-400 to-emerald-400 text-white text-xs font-bold rounded-full hover:shadow-lg transition">
                                                ✓ Terima
                                            </button>
                                        </form>
                                        <form action="{{ route('friends.reject', $req->id) }}" method="POST">
                                            @csrf
                                            <button class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-full hover:bg-gray-300 transition">
                                                ✗ Tolak
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Outgoing Requests --}}
                    @if($outgoing->count())
                        <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-2xl p-4 border border-gray-200">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-xl">⏳</span>
                                <h3 class="font-bold text-gray-700">Permintaan Terkirim</h3>
                            </div>
                            @foreach($outgoing as $req)
                                <div class="flex items-center justify-between p-3 bg-white rounded-xl mb-2">
                                    <span class="text-gray-900 font-medium">{{ $req->receiver->name }}</span>
                                    @if($req->status === 'rejected')
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-red-500 font-semibold bg-red-50 px-2 py-1 rounded-full">Ditolak</span>
                                            <form action="{{ route('friends.clear', $req->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="w-6 h-6 bg-red-100 text-red-500 rounded-full hover:bg-red-200 transition flex items-center justify-center">
                                                    ✗
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 font-medium">Menunggu...</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add Friend --}}
                    <div class="bg-gradient-to-r from-rose-400 via-pink-400 to-purple-400 rounded-2xl p-4 shadow-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">➕</span>
                            <h3 class="font-bold text-white">Tambah Teman Baru</h3>
                        </div>
                        <form action="{{ route('friends.send') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="email" name="email" placeholder="✉️ Email teman..."
                                class="flex-1 px-4 py-3 border-0 rounded-xl text-sm focus:ring-4 focus:ring-white/50 shadow-lg" required>
                            <button class="px-5 py-3 bg-white text-rose-500 rounded-xl hover:shadow-xl transition-all hover:scale-105 font-bold text-sm">
                                Kirim
                            </button>
                        </form>
                    </div>

                    {{-- Search --}}
                    <div class="relative">
                        <input id="friendSearch" type="text" placeholder="🔍 Cari kontak..."
                            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-rose-100 focus:border-rose-300">
                    </div>

                    {{-- Contacts List --}}
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-4 border border-purple-200">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">👤</span>
                            <h3 class="font-bold text-gray-800">Semua Kontak</h3>
                            <span class="ml-auto text-xs font-bold text-purple-600 bg-purple-100 px-3 py-1 rounded-full">
                                {{ $friends->count() }}
                            </span>
                        </div>

                        @forelse($friends as $friend)
                            <form data-friend="{{ strtolower($friend->name) }}" action="{{ route('chat.create') }}" method="POST"
                                class="flex items-center justify-between p-3 bg-white rounded-xl hover:shadow-md transition mb-2">
                                @csrf
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="relative">
                                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 p-0.5">
                                            <img src="{{ $friend->avatar ? asset('storage/'.$friend->avatar) : asset('images/default-avatar.png') }}"
                                                class="w-full h-full rounded-full object-cover bg-white">
                                        </div>
                                        @if($friend->is_online)
                                            <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-400 border-2 border-white rounded-full shadow-lg"></span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate text-sm">{{ $friend->name }}</h4>
                                        <p class="text-xs {{ $friend->is_online ? 'text-green-500' : 'text-gray-400' }} font-medium">
                                            {{ $friend->is_online ? '🟢 Online' : '⚪ Offline' }}
                                        </p>
                                    </div>
                                </div>

                                <input type="hidden" name="user_id" value="{{ $friend->id }}">
                                <button class="px-4 py-2 bg-gradient-to-r from-rose-400 to-pink-400 text-white text-xs font-bold rounded-full hover:shadow-lg transition-all hover:scale-105">
                                    💬 Chat
                                </button>
                            </form>
                        @empty
                            <div class="p-8 text-center">
                                <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-purple-100 to-pink-100 rounded-full flex items-center justify-center">
                                    <span class="text-4xl">😢</span>
                                </div>
                                <p class="text-sm font-semibold text-gray-700 mb-1">Belum ada kontak</p>
                                <p class="text-xs text-gray-500">Yuk tambah teman pakai email!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

        {{-- KONTEN KANAN - IFRAME CHAT --}}
        <div class="hidden md:flex flex-1 relative overflow-hidden bg-white">
            {{-- Empty State (Default) --}}
            <div id="emptyState" class="flex-1 flex items-center justify-center">
                {{-- Animated Background --}}
                <div class="absolute inset-0">
                    <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-rose-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                    <div class="absolute top-1/3 right-1/4 w-64 h-64 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
                    <div class="absolute bottom-1/4 left-1/3 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
                </div>

                {{-- Content --}}
                <div class="relative text-center px-8 z-10">
                    <div class="w-40 h-40 mx-auto mb-8 bg-gradient-to-br from-rose-400 via-pink-400 to-purple-500 rounded-[3rem] flex items-center justify-center shadow-2xl transform rotate-6 hover:rotate-12 transition-all duration-500">
                        <div class="transform -rotate-6 hover:-rotate-12 transition-all duration-500">
                            <span class="text-7xl">💬</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-rose-500 via-pink-500 to-purple-500 bg-clip-text text-transparent mb-3">
                        Mulai Ngobrol Yuk! 🎉
                    </h3>
                    <p class="text-gray-500 max-w-md text-lg font-medium">
                        Pilih chat di sebelah kiri atau tambahkan teman baru untuk mulai ngobrol seru!
                    </p>
                </div>
            </div>

            {{-- iFrame untuk load chat.show --}}
            <iframe id="chatFrame" 
                    name="chatFrame" 
                    class="hidden w-full h-full border-0"
                    onload="handleIframeLoad()">
            </iframe>
        </div>

    </div>

    <style>
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        .animate-bounce-slow {
            animation: bounce 1s ease-in-out 2;
        }
        .chat-room-item.active {
            background: linear-gradient(to right, #fce7f3, #fae8ff);
            border-color: #f472b6;
        }
    </style>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-white', 'bg-gradient-to-r', 'from-rose-400', 'to-pink-400', 'shadow-lg', 'scale-105');
                btn.classList.add('text-gray-600', 'bg-gray-100');
            });
            
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            activeBtn.classList.remove('text-gray-600', 'bg-gray-100');
            activeBtn.classList.add('text-white', 'bg-gradient-to-r', 'from-rose-400', 'to-pink-400', 'shadow-lg', 'transform', 'scale-105');
        }

        function setActiveChat(element, conversationId) {
            // Remove active class dari semua chat items
            document.querySelectorAll('.chat-room-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class ke chat yang diklik
            element.classList.add('active');
            
            // Hide empty state, show iframe
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('chatFrame').classList.remove('hidden');
        }

        function handleIframeLoad() {
            // Fungsi ini dipanggil ketika iframe selesai load
            // Bisa digunakan untuk menangani loading state
            console.log('Chat loaded');
        }

        // Search functionality
        document.getElementById('chatSearch').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('[data-chat-item]').forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });

        document.getElementById('friendSearch').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('[data-friend]').forEach(item => {
                item.style.display = item.dataset.friend.includes(query) ? '' : 'none';
            });
        });

        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.flash');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.5s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>

</x-app-layout>