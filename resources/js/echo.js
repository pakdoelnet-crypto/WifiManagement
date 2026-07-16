import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const wsHost = import.meta.env.VITE_REVERB_HOST === 'localhost' || !import.meta.env.VITE_REVERB_HOST
    ? window.location.hostname
    : import.meta.env.VITE_REVERB_HOST;

const wsPort = import.meta.env.VITE_REVERB_PORT || 8080;
const wssPort = import.meta.env.VITE_REVERB_PORT || 8080;
const forceTLS = window.location.protocol === 'https:';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || '11ywzgciy0sbqz1yo2ls',
    wsHost: wsHost,
    wsPort: wsPort,
    wssPort: wssPort,
    forceTLS: forceTLS,
    enabledTransports: ['ws', 'wss'],
});
