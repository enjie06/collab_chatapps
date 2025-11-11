import axios from 'axios';
// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';

// Set axios ke window
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ==== Nonaktifkan sementara kalau belum pakai broadcast / realtime ====
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
