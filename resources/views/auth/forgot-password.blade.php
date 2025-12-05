<x-guest-layout>
    
        <div class="bg-white/90 shadow-md rounded-2xl w-full max-w-sm p-8">

            <div class="flex flex-col items-center mb-4">
                <div class="bg-rose-400 rounded-full p-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         class="h-10 w-10 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 18a8 8 0 1116 0H2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-rose-600">Forgot Password</h2>
            </div>

            <div class="mb-4 text-sm text-rose-700 text-center">
                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-4">
                    <x-input-label for="email" :value="__('Email')" class="text-rose-700" />

                    <x-text-input id="email"
                        class="block mt-1 w-full border-b border-rose-300 bg-transparent
                               focus:border-rose-500 focus:ring-0 outline-none"
                        type="email" name="email" :value="old('email')" required autofocus
                        placeholder="your@email.com" />

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button
                        class="w-full py-2 bg-rose-500 text-white font-semibold rounded-full
                               hover:bg-rose-600 transition">
                        {{ __('Email Password Reset Link') }}
                    </button>
                </div>
            </form>

            <div class="text-center mt-4 text-sm text-rose-700">
                <a href="{{ route('login') }}" class="hover:underline">Back to login</a>
            </div>
        </div>

</x-guest-layout>