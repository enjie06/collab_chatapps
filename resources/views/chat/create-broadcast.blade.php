<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-rose-700">Buat Broadcast</h2>
    </x-slot>

    <div class="max-w-xl mx-auto mt-6 bg-white p-6 rounded-lg shadow">
        <form action="{{ route('broadcast.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nama Broadcast</label>
                <input type="text" name="title" class="w-full border rounded-lg px-3 py-2"
                       placeholder="Masukkan nama broadcast..." required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Pilih Anggota</label>

                @forelse($friends as $friend)
                    <label class="flex items-center gap-2 mb-2">
                        <input type="checkbox" name="member_ids[]" value="{{ $friend->id }}">
                        <span>{{ $friend->name }}</span>
                    </label>
                @empty
                    <p class="text-gray-500 text-sm">Belum punya teman untuk ditambahkan.</p>
                @endforelse
            </div>

            <button class="w-full bg-rose-600 hover:bg-rose-700 text-white py-2 rounded-lg">
                Buat Broadcast
            </button>
        </form>
    </div>
</x-app-layout>
