<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-rose-700">
            Profil {{ $user->name }}
        </h2>
    </x-slot>

    <div class="max-w-md mx-auto mt-6">

        <div class="rounded-2xl bg-white border border-rose-100 shadow p-6
                    flex flex-col items-center text-center space-y-4">

            {{-- FOTO --}}
            <div class="relative">
                <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/default-avatar.png') }}"
                     class="w-28 h-28 rounded-full object-cover border shadow">

                @if($user->is_online)
                    <span class="absolute bottom-1 right-1 w-4 h-4 bg-green-500 
                                  border-2 border-white rounded-full"></span>
                @endif
            </div>

            {{-- NAMA --}}
            <h1 class="text-xl font-semibold text-gray-800">
                {{ $user->name }}
            </h1>

            {{-- EMAIL --}}
            <p class="text-gray-500 text-sm mb-1">
                {{ $user->email }}
            </p>

            {{-- STATUS --}}
            <div class="mt-1 mb-2">
                @if($user->is_online)
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs">
                        ● Online
                    </span>
                @else
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                        ● Offline
                    </span>
                @endif
            </div>

            {{-- INFO --}}
            <div class="text-xs text-gray-500 space-y-1">
                <p>
                    Bergabung sejak:
                    <span class="text-gray-700 font-medium">
                        {{ $user->created_at?->format('d M Y') }}
                    </span>
                </p>

                <p>
                    Terakhir aktif:
                    <span class="text-gray-700 font-medium">
                        @if($user->last_seen_at)
                            {{ $user->last_seen_at->diffForHumans() }}
                        @else
                            Tidak diketahui
                        @endif
                    </span>
                </p>
            </div>

            {{-- TOMBOL KEMBALI --}}
            <div class="pt-3">
                <a href="{{ url()->previous() }}"
                    class="inline-block bg-rose-600 text-white px-2 py-1.5 rounded-lg 
                           text-sm hover:bg-rose-700 transition">
                    ← Kembali ke Chat
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
