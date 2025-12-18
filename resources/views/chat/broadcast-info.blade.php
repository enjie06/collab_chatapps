<x-app-layout>
    <div class="max-w-md mx-auto mt-4 p-4 bg-white border rounded-lg shadow-sm">

        <!-- TOMBOL KEMBALI -->
        <button onclick="window.location='{{ route('chat.show', $broadcast->id) }}'"
            class="bg-rose-500 border border-rose-600 text-white text-xs font-semibold 
                px-3 py-1.5 rounded-lg hover:bg-rose-600 transition mb-3">
            ← Kembali
        </button>

        <!-- FOTO & INFO BROADCAST -->
        <div class="flex flex-col items-center mb-4">
            <img src="{{ $broadcast->avatar ? asset('storage/'.$broadcast->avatar) : asset('images/default-group.png') }}"
                 class="w-20 h-20 rounded-full border object-cover mb-3">

            <h2 class="text-lg font-semibold text-gray-800">{{ $broadcast->name }}</h2>
            <p class="text-xs text-gray-500">{{ $members->count() }} anggota</p>
        </div>

        <hr class="my-4">

        @if($isAdmin)
            <div class="mb-6">

                {{-- SUCCESS --}}
                @if(session('success'))
                    <div id="flash-success"
                        class="mb-3 text-xs text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- ERROR --}}
                @if(session('error'))
                    <div id="flash-error"
                        class="mb-3 text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        {{ session('error') }}
                    </div>
                @endif

                <button
                    onclick="document.getElementById('addMemberBox').classList.toggle('hidden')"
                    class="w-full text-sm font-medium py-2 mb-3 border rounded-lg hover:bg-gray-50">
                    + Tambah Anggota
                </button>

                <form id="addMemberBox"
                    method="POST"
                    action="{{ route('broadcast.add', $broadcast->id) }}"
                    class="hidden">
                    @csrf

                    <div class="max-h-40 overflow-y-auto rounded-lg border bg-gray-50 p-3 mb-3 space-y-1">

                        @php
                            $availableFriends = isset($friends)
                                ? $friends->filter(fn($f) => !$members->contains($f->id))
                                : collect();
                        @endphp

                        @if($availableFriends->isEmpty())
                            <p class="text-xs text-gray-500">
                                Semua teman sudah menjadi anggota broadcast.
                            </p>
                        @else
                            @foreach($availableFriends as $friend)
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white px-2 py-1 rounded-md">
                                    <input type="checkbox"
                                        name="user_id[]"
                                        value="{{ $friend->id }}"
                                        class="accent-rose-500">
                                    <span>{{ $friend->name }}</span>
                                </label>
                            @endforeach
                        @endif

                    </div>

                    <button
                        class="w-full bg-rose-500 text-white text-sm font-medium py-2 rounded-lg hover:bg-rose-600 transition">
                        Tambahkan
                    </button>
                </form>
            </div>
        @endif

        <!-- DAFTAR ANGGOTA -->
        <h3 class="font-semibold text-gray-700 mb-2">Anggota</h3>

        @php
        $sortedMembers = $members->sortByDesc(fn($m) => $m->pivot->role === 'admin');
        @endphp

        @foreach($sortedMembers as $m)
            <div class="p-3 border rounded-lg mb-2 bg-white flex items-center justify-between">

                <!-- KIRI: Avatar + Info -->
                <div class="flex items-center gap-3">
                    <img
                        src="{{ $m->avatar ? asset('storage/'.$m->avatar) : asset('images/default-avatar.png') }}"
                        class="w-10 h-10 rounded-full object-cover border"
                    >

                    <div>
                        <p class="font-medium text-gray-800 leading-tight">
                            {{ $m->name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $m->email }}
                        </p>
                    </div>
                </div>

                <!-- KANAN: Status + Aksi -->
                <div class="flex flex-col items-end gap-1 text-xs">

                    {{-- STATUS --}}
                    @if($m->pivot->role === 'admin')
                        <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full font-semibold">
                            Admin
                        </span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full font-medium">
                            Member
                        </span>
                    @endif

                    {{-- AKSI --}}
                    @if($isAdmin && $m->pivot->role !== 'admin')
                        <form method="POST"
                            action="{{ route('broadcast.remove', [$broadcast->id, $m->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-500 text-[11px] hover:underline">
                                Keluarkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

<script>
    ['flash-success', 'flash-error'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        setTimeout(() => {
            el.classList.add('opacity-0', 'transition', 'duration-500');
            setTimeout(() => el.remove(), 500);
        }, 3000);
    });
</script>

</x-app-layout>
