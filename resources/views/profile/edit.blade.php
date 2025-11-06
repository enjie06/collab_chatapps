<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Tambahan Avatar Section -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl text-center">
                    @if (Auth::user()->avatar)
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                             alt="Avatar" 
                             class="w-24 h-24 rounded-full mx-auto mb-2">
                    @else
                        <img src="{{ asset('images/default-avatar.png') }}" 
                             alt="Default Avatar" 
                             class="w-24 h-24 rounded-full mx-auto mb-2">
                    @endif

                     <!-- {{-- Status online/offline --}} -->
                    @if (Auth::user()->is_online)
                        <span class="text-green-500">● Online</span>
                    @else
                        <span class="text-gray-400">● Offline</span>
                    @endif

                    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-4">
                        @csrf
                        @method('patch')
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Ganti Avatar
                        </label>
                        <input type="file" name="avatar"
                               class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <x-primary-button class="mt-3">
                            {{ __('Update Avatar') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
            <!-- Akhir Tambahan Avatar -->

            <!-- Form Update Profile -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Form Ubah Password -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Form Hapus Akun -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
