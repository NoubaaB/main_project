import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Initialize the WebSocket connection via Laravel Echo + Reverb.
 * Must be called AFTER the user is authenticated (token in localStorage).
 */
export function initSocket() {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        authEndpoint: '/api/broadcasting/auth',
        auth: {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'Accept': 'application/json',
            },
        },
        enabledTransports: ['ws', 'wss'],
    });
}

/**
 * Disconnect the WebSocket connection (on logout).
 */
export function disconnectSocket() {
    if (window.Echo) {
        window.Echo.disconnect();
        window.Echo = null;
    }
}