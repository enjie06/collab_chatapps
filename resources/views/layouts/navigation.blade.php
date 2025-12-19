<nav x-data="{ open: false }" class="bg-gradient-to-r from-rose-500 via-pink-500 to-rose-600 shadow-lg sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="bg-white rounded-full p-2 shadow-md group-hover:scale-110 group-hover:shadow-lg transition-all duration-300">
                        <img src= "{{ asset('images/logo fluffy.jpg') }}"alt="Logo" class="h-8 w-8 object-contain" />                        </div>
                        <span class="text-white font-bold text-xl tracking-wide">Fluffy</span>
                    </a>
                </div>

                
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center space-x-3 px-5 py-2.5 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-sm transition-all duration-300 shadow-md hover:shadow-lg group">
                        <div class="flex items-center space-x-3">
                            <div class="bg-white rounded-full p-1.5 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 18a8 8 0 1116 0H2z"/>
                                </svg>
                            </div>
                            <span class="text-white font-semibold text-sm">{{ Auth::user()->name }}</span>
                        </div>
                        <svg class="w-4 h-4 text-white transition-transform duration-300" 
                             :class="{'rotate-180': open}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display: none;"
                         class="absolute right-0 mt-3 w-64 rounded-2xl shadow-2xl bg-white ring-1 ring-black ring-opacity-5 overflow-hidden z-50">
                        
                        <!-- User Info -->
                        <div class="px-5 py-4 bg-gradient-to-r from-rose-50 via-pink-50 to-rose-100 border-b border-pink-200">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-rose-500 via-pink-500 to-rose-600 rounded-full p-2">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 18a8 8 0 1116 0H2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-rose-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-pink-600 truncate">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-2">
                            <a href="{{ route('profile.edit') }}" 
                               class="flex items-center px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gradient-to-r hover:from-rose-50 hover:via-pink-50 hover:to-rose-100 hover:text-rose-600 transition-all duration-200 group">
                                <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profile
                            </a>
                        </div>

                        <!-- Logout -->
                        <div class="border-t border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center w-full px-5 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-all duration-200 group">
                                    <svg class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" 
                        class="inline-flex items-center justify-center p-2.5 rounded-xl text-white hover:bg-white/20 focus:outline-none transition-all duration-300">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" 
                              class="inline-flex" 
                              stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="2.5" 
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open}" 
                              class="hidden" 
                              stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="2.5" 
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': !open}" 
         class="hidden sm:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        
        <!-- Mobile Navigation Links -->
        <div class="pt-2 pb-3 space-y-1 bg-white/95 backdrop-blur-sm">
            <a href="{{ route('dashboard') }}" 
               class="flex items-center px-4 py-3 text-base font-semibold border-l-4 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-rose-50 via-pink-50 to-rose-100 border-rose-600 text-rose-600' : 'border-transparent text-gray-700 hover:bg-gradient-to-r hover:from-rose-50 hover:via-pink-50 hover:to-rose-100 hover:border-pink-500 hover:text-pink-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-3 border-t border-gray-200 bg-white/95 backdrop-blur-sm">
            <div class="px-4 mb-3">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-rose-500 via-pink-500 to-rose-600 rounded-full p-2.5 shadow-md">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 18a8 8 0 1116 0H2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center px-4 py-3 text-base font-medium text-gray-700 hover:bg-gradient-to-r hover:from-rose-50 hover:via-pink-50 hover:to-rose-100 hover:text-rose-600 transition-all duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile
                </a>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="flex items-center w-full px-4 py-3 text-base font-medium text-red-600 hover:bg-red-50 transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>