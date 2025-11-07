<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-red-700 leading-tight">
            {{ __('Profile Saya') }}
        </h2>
    </x-slot>

    <div class="py-12" style="background-color: #fff8f4;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Avatar Section -->
            <div class="p-6 bg-white shadow-lg rounded-2xl border border-red-100">
                <div class="max-w-xl mx-auto text-center space-y-3">
                    <div class="relative inline-block">
                        <img id="avatarPreview" 
                            src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('images/default-avatar.png') }}" 
                            alt="Avatar" 
                            class="w-28 h-28 rounded-full mx-auto shadow-md border-4 border-red-200 object-cover cursor-pointer">

                        <!-- Bulatan status (pojok kanan bawah avatar) -->
                        @if (Auth::user()->is_online)
                            <span class="absolute bottom-0 right-0 block w-5 h-5 bg-green-500 border-2 border-white rounded-full shadow"></span>
                        @endif
                    </div>

                    <h3 class="text-lg font-bold text-red-700">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <!-- Modal Preview Avatar -->
            <div id="avatarModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
                <div class="relative">
                    <img id="avatarModalImg" src="" alt="Preview Avatar" class="max-w-sm sm:max-w-md rounded-2xl shadow-2xl border-4 border-white transform scale-95 transition-transform duration-300">
                    <button id="closeAvatarModal" 
                            class="absolute top-2 right-2 bg-white bg-opacity-80 text-gray-700 rounded-full p-2 hover:bg-opacity-100 transition">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Form Update Profil & Avatar -->
            <div class="p-6 bg-white shadow-lg rounded-2xl border border-red-100">
                <h3 class="text-lg font-semibold text-red-700 mb-4">Informasi Profil & Foto Profil</h3>
                <div class="max-w-xl space-y-6">

                    <form id="profileForm" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')

                        <!-- Nama -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                                   class="w-full rounded-lg border border-red-200 p-2 focus:ring-red-300 focus:border-red-400" />
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                                   class="w-full rounded-lg border border-red-200 p-2 focus:ring-red-300 focus:border-red-400" />
                        </div>

                        <!-- Upload Avatar -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ganti Profil</label>
                            <input type="file" name="avatar" id="avatarInput"
                                   class="block w-full text-sm text-gray-900 border border-red-200 rounded-lg cursor-pointer bg-cream-50 focus:ring-red-300 focus:border-red-400" />
                        </div>

                        <!-- Modal Crop -->
                        <div id="cropModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white rounded-2xl p-6 shadow-xl max-w-md w-full">
                                <h3 class="text-lg font-semibold text-red-700 mb-4 text-center">Sesuaikan Foto Profil</h3>
                                <div class="w-full h-64 mb-4 overflow-hidden rounded-xl">
                                    <img id="imageToCrop" class="max-w-full" />
                                </div>
                                <div class="flex justify-end space-x-3">
                                    <button type="button" id="cancelCrop" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Batal</button>
                                    <button type="button" id="saveCrop" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Simpan</button>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Simpan -->
                        <div class="mt-6 flex items-center justify-between">
                            <div></div>
                            <x-primary-button class="bg-green-500 hover:bg-red-600 text-white rounded-lg px-4 py-2 transition">
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <!-- Form Hapus Avatar -->
                    <div class="pt-3 border-t border-red-100">
                        <form method="post" action="{{ route('profile.avatar.destroy') }}" 
                              onsubmit="return confirm('Yakin ingin menghapus avatar? Avatar akan kembali ke gambar default.');">
                            @csrf
                            @method('delete')
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-500">Hapus foto profil jika ingin kembali ke default</p>
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-lg text-red-600 hover:bg-red-50 transition">
                                    Hapus Foto Profil
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Ubah Password -->
            <div class="p-6 bg-white shadow-lg rounded-2xl border border-red-100">
                <h3 class="text-lg font-semibold text-red-700 mb-4">Ubah Password</h3>
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Hapus Akun -->
            <div class="p-6 bg-white shadow-lg rounded-2xl border border-red-100">
                <h3 class="text-lg font-semibold text-red-700 mb-4">Hapus Akun</h3>
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>

    <!-- Tambah Script Cropper -->
    <link  href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
        const avatarInput = document.getElementById('avatarInput');
        const cropModal = document.getElementById('cropModal');
        const imageToCrop = document.getElementById('imageToCrop');
        const avatarPreview = document.getElementById('avatarPreview');
        let cropper;

        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                imageToCrop.src = reader.result;
                cropModal.classList.remove('hidden');
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 1,
                });
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('cancelCrop').addEventListener('click', () => {
            cropper.destroy();
            cropModal.classList.add('hidden');
            avatarInput.value = '';
        });

        document.getElementById('saveCrop').addEventListener('click', () => {
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
            });
            canvas.toBlob((blob) => {
                const file = new File([blob], 'cropped-avatar.png', { type: 'image/png' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                avatarInput.files = dataTransfer.files;

                avatarPreview.src = URL.createObjectURL(file);
                cropper.destroy();
                cropModal.classList.add('hidden');
            });
        });

        // === Avatar Click Preview ===
        const avatarModal = document.getElementById('avatarModal');
        const avatarModalImg = document.getElementById('avatarModalImg');
        const closeAvatarModal = document.getElementById('closeAvatarModal');

        avatarPreview.addEventListener('click', () => {
            avatarModalImg.src = avatarPreview.src;
            avatarModal.classList.remove('hidden');
            setTimeout(() => {
                avatarModalImg.classList.remove('scale-95');
                avatarModalImg.classList.add('scale-100');
            }, 10);
        });

        closeAvatarModal.addEventListener('click', () => {
            avatarModal.classList.add('hidden');
            avatarModalImg.classList.add('scale-95');
        });

        avatarModal.addEventListener('click', (e) => {
            if (e.target === avatarModal) {
                avatarModal.classList.add('hidden');
                avatarModalImg.classList.add('scale-95');
            }
        });
    </script>
</x-app-layout>
