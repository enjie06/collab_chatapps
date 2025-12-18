@php use Illuminate\Support\Str; @endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-rose-700">Chat</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 flex flex-col gap-8 md:grid md:grid-cols-2">

        {{-- Flash --}}
        @if(session('success'))
            <div class="flash md:col-span-2 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flash md:col-span-2 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg">
                {{ session('error') }}
            </div>
        @endif


        {{-- === Side Section: Permintaan | Tambah Teman | Grup | Kontak === --}}
        <div class="order-1 md:order-2">

            {{-- Permintaan Masuk --}}
            @if($incoming->count())
                <h3 class="font-semibold text-lg text-gray-700 mb-2">Permintaan Masuk</h3>
                @foreach($incoming as $req)
                    <div class="bg-white border rounded-lg p-3 flex justify-between items-center mb-2">
                        <span>{{ $req->requester->name }}</span>
                        <div class="flex gap-2">
                            <form action="{{ route('friends.accept', $req->id) }}" method="POST">@csrf
                                <button class="text-green-600 hover:underline">Terima</button>
                            </form>
                            <form action="{{ route('friends.reject', $req->id) }}" method="POST">@csrf
                                <button class="text-red-600 hover:underline">Tolak</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Permintaan Keluar --}}
            @if($outgoing->count())
                <h3 class="font-semibold text-lg text-gray-700 mt-6 mb-2">Permintaan yang Kamu Kirim</h3>
                @foreach($outgoing as $req)
                    <div class="bg-white border rounded-lg p-3 flex justify-between items-center mb-2 text-sm">
                        <span>{{ $req->receiver->name }}</span>
                        @if($req->status === 'rejected')
                            <span class="text-red-500 italic">Ditolak</span>
                            <form action="{{ route('friends.clear', $req->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="text-gray-400 hover:text-red-600">x</button>
                            </form>
                        @else
                            <span class="italic text-gray-500">Menunggu…</span>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- Tambah Teman --}}
            <h3 class="font-semibold text-lg text-gray-700 mb-3 mt-6">Tambah Teman</h3>
            <form action="{{ route('friends.send') }}" method="POST" class="flex gap-2 mb-6">
                @csrf
                <input type="email" name="email" placeholder="Masukkan email teman..."
                    class="flex-1 border rounded-lg px-3 py-2 focus:ring-rose-300 focus:border-rose-500" required>
                <button class="bg-rose-500 text-white px-4 py-2 rounded-lg hover:bg-rose-600 transition">
                    Kirim
                </button>
            </form>

            {{-- === Broadcast === --}}
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-lg text-gray-700">Broadcast</h3>

                {{-- Tombol kecil buat broadcast --}}
                <a href="{{ route('broadcast.create') }}"
                    class="text-xs bg-rose-500 text-white px-2 py-1 rounded hover:bg-rose-600 transition">
                    + Broadcast
                </a>
            </div>

            {{-- List Broadcast --}}
            @foreach(
                $conversations
                    ->where('type', 'broadcast')
                    ->filter(function($g) {
                        $myPivot = $g->users->firstWhere('id', auth()->id())?->pivot;
                        return is_null($myPivot?->deleted_at);
                    })
                as $broadcast
            )
                <a href="{{ route('chat.show', $broadcast->id) }}"
                    class="block p-3 mb-2 bg-white border rounded-lg hover:bg-rose-50 transition">

                    <div class="flex items-center gap-3">
                        <img src="{{ $broadcast->avatar ? asset('storage/'.$group->avatar) : asset('images/default-group.png') }}"
                            class="w-9 h-9 rounded-full object-cover border">

                        <div class="flex-1">
                            <strong class="text-gray-800">{{ $broadcast->name }}</strong>

                            <div class="text-xs text-gray-500">
                                {{ $broadcast->messages->count() ? Str::limit($broadcast->messages->last()->content, 40) : 'Belum ada pesan.' }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach

            {{-- === GRUP === --}}
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-lg text-gray-700">Grup</h3>

                {{-- Tombol kecil buat grup --}}
                <a href="{{ route('group.create') }}"
                    class="text-xs bg-rose-500 text-white px-2 py-1 rounded hover:bg-rose-600 transition">
                    + Grup
                </a>
            </div>

            {{-- List Grup --}}
            @foreach(
                $conversations
                    ->where('type', 'group')
                    ->filter(function($g) {
                        $myPivot = $g->users->firstWhere('id', auth()->id())?->pivot;
                        return is_null($myPivot?->deleted_at);
                    })
                as $group
            )
                <a href="{{ route('chat.show', $group->id) }}"
                    class="block p-3 mb-2 bg-white border rounded-lg hover:bg-rose-50 transition">

                    <div class="flex items-center gap-3">
                        <img src="{{ $group->avatar ? asset('storage/'.$group->avatar) : asset('images/default-group.png') }}"
                            class="w-9 h-9 rounded-full object-cover border">

                        <div class="flex-1">
                            <strong class="text-gray-800">{{ $group->name }}</strong>

                            <div class="text-xs text-gray-500">
                                {{ $group->messages->count() ? Str::limit($group->messages->last()->content, 40) : 'Belum ada pesan.' }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach

            {{-- === Kontak === --}}
            <h3 class="font-semibold text-lg text-gray-700 mt-6 mb-2">Kontak</h3>

            <input id="friendSearch" type="text" placeholder="Cari nama..."
                class="w-full mb-3 border px-3 py-2 rounded-lg text-sm">

            @forelse($friends as $friend)
                <form data-friend="{{ strtolower($friend->name) }}" action="{{ route('chat.create') }}"
                    method="POST"
                    class="flex items-center justify-between p-3 bg-white border rounded-lg mb-2 hover:bg-rose-50 transition">
                    @csrf
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <img src="{{ $friend->avatar ? asset('storage/'.$friend->avatar) : asset('images/default-avatar.png') }}"
                                class="w-9 h-9 rounded-full object-cover border">

                            @if($friend->is_online)
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border border-white rounded-full"></span>
                            @endif
                        </div>
                        <span class="font-medium text-gray-800">{{ $friend->name }}</span>
                    </div>

                    <input type="hidden" name="user_id" value="{{ $friend->id }}">
                    <button class="bg-rose-500 text-white text-sm px-3 py-1 rounded-lg hover:bg-rose-600 transition">
                        Chat
                    </button>
                </form>
            @empty
                <p class="text-gray-600 text-sm">Belum ada teman.</p>
            @endforelse

        </div>

        {{-- === Percakapan Aktif === --}}
        <div class="order-2 md:order-1">
            <h3 class="font-semibold text-lg text-gray-700 mb-3">Percakapan Aktif</h3>

            @forelse($conversations as $conversation)
                @php
                    $isGroup = $conversation->type === 'group';
                    $lastMsg = $conversation->last_visible_message;
                    $myRead = $conversation->users->firstWhere('id', auth()->id())?->pivot?->last_read_message_id ?? 0;
                    $hasUnread = $lastMsg && $lastMsg->user_id != auth()->id() && $myRead < $lastMsg->id;

                    // Untuk private
                    $partner = !$isGroup
                        ? $conversation->users->firstWhere('id', '!=', auth()->id())
                        : null;
                @endphp

                <a href="{{ route('chat.show', $conversation->id) }}"
                class="block p-4 mb-3 bg-white border rounded-lg hover:bg-rose-50 transition relative">

                    <div class="flex items-center gap-3">

                        {{-- FOTO --}}
                        @if($isGroup)
                            <div class="relative">
                                <img src="{{ $conversation->avatar ? asset('storage/'.$conversation->avatar) : asset('images/default-group.png') }}"
                                    class="w-10 h-10 rounded-full object-cover border">
                            </div>
                        @else
                            <div class="relative">
                                <img src="{{ $partner?->avatar ? asset('storage/'.$partner->avatar) : asset('images/default-avatar.png') }}"
                                    class="w-10 h-10 rounded-full object-cover border">
                            </div>
                        @endif

                        {{-- NAMA --}}
                        <div class="flex-1">
                            <strong class="text-rose-600">
                                @if($conversation->type === 'group' || $conversation->type === 'broadcast')
                                    {{ $conversation->name }}
                                @else
                                    {{ $partner?->name ?? 'User tidak ditemukan' }}
                                @endif
                            </strong>

                            <div class="text-xs text-gray-500">
                                {{ $lastMsg ? Str::limit($lastMsg->content, 40) : 'Belum ada pesan.' }}
                            </div>
                        </div>
                    </div>

                    @if($hasUnread)
                        <span class="absolute top-1/2 -translate-y-1/2 right-3 w-3 h-3 bg-rose-600 rounded-full"></span>
                    @endif
                </a>

            @empty
                <p class="text-gray-600 text-sm">Belum ada percakapan.</p>
            @endforelse
        </div>
    </div>

    <script>
    document.getElementById('friendSearch').addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('[data-friend]').forEach(el => {
            el.style.display = el.dataset.friend.includes(q) ? '' : 'none';
        });
    });
    </script>

</x-app-layout>
