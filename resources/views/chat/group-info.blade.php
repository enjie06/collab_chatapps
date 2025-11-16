<x-app-layout>
    <div class="max-w-md mx-auto mt-4 p-4 bg-white border rounded-lg shadow-sm">

        <!-- TOMBOL KEMBALI -->
        <button onclick="window.location='{{ route('chat.show', $group->id) }}'"
            class="bg-rose-500 border border-rose-600 text-white text-xs font-semibold 
                px-3 py-1.5 rounded-lg hover:bg-rose-600 transition mb-3">
            ← Kembali
        </button>

        <!-- FOTO & INFO GRUP -->
        <div class="flex flex-col items-center mb-4">
            <img src="{{ $group->avatar ? asset('storage/'.$group->avatar) : asset('images/default-group.jpeg') }}"
                 class="w-20 h-20 rounded-full border object-cover mb-3">

            <h2 class="text-lg font-semibold text-gray-800">{{ $group->name }}</h2>
            <p class="text-xs text-gray-500">{{ $members->count() }} anggota</p>
        </div>

        <hr class="my-4">

        <!-- DAFTAR ANGGOTA -->
        <h3 class="font-semibold text-gray-700 mb-2">Anggota</h3>

        @php
        $sortedMembers = $members->sortByDesc(fn($m) => $m->pivot->role === 'admin');
        @endphp

        @foreach($sortedMembers as $m)
            <div class="p-3 border rounded-lg mb-2 bg-white">
                <div class="flex items-center justify-between">
                    
                    <!-- Foto + Nama + Email -->
                    <div class="flex items-center gap-3">
                        <img src="{{ $m->avatar ? asset('storage/'.$m->avatar) : asset('images/default-avatar.png') }}"
                            class="w-10 h-10 rounded-full object-cover border">

                        <div>
                            <p class="font-medium text-gray-800 flex items-center gap-2">

                                {{ $m->name }}

                            </p>

                            <p class="text-xs text-gray-500">{{ $m->email }}</p>
                        </div>
                    </div>

                    <!-- ROLE di kanan -->
                    @if($m->pivot->role === 'admin')
                        <span class="text-[11px] px-2 py-1 bg-rose-100 text-rose-700 rounded-full font-semibold">
                            Admin
                        </span>
                    @endif

                </div>
            </div>
        @endforeach

    </div>
</x-app-layout>
