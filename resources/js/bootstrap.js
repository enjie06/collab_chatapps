import axios from 'axios';
import Echo from 'laravel-echo';

// Alpine
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Set axios ke window
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// REVERB (WebSockets)
window.Echo = new Echo({
    broadcaster: 'reverb',
    wsHost: window.location.hostname,   // otomatis lokal / server
    wsPort: 6001,                        // ini harus sama di config/reverb.php
    wssPort: 6001,
    forceTLS: false,                     // local → false
    enabledTransports: ['ws', 'wss'],    // pastikan bisa konek
});
