import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

/**
 * Initialize the WebSocket connection via Laravel Echo + Reverb.
 * Must be called AFTER the user is authenticated (token in localStorage).
 */
export function initSocket() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.warn('[WebSocket] Cannot init socket: no token in localStorage');
        return null;
    }

    // If Echo is already connected with valid socket, reuse it
    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
        const state = window.Echo.connector.pusher.connection.state;
        if (state === 'connected' || state === 'connecting') {
            return window.Echo;
        }
    }

    // Safely disconnect previous instance if any
    disconnectSocket();

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        authEndpoint: '/api/broadcasting/auth',
        authorizer: (channel, options) => {
            return {
                authorize: (socketId, callback) => {
                    const currentToken = localStorage.getItem('token');
                    if (!currentToken) {
                        callback(new Error('No authentication token found in localStorage'), null);
                        return;
                    }
                    if (!socketId) {
                        callback(new Error('Missing socket_id for channel authorization'), null);
                        return;
                    }

                    axios.post('/api/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name,
                    }, {
                        headers: {
                            'Authorization': 'Bearer ' + currentToken,
                            'Accept': 'application/json',
                        },
                    })
                    .then(response => {
                        callback(null, response.data);
                    })
                    .catch(error => {
                        console.error('[WebSocket Auth Error]', error?.response?.data || error.message);
                        callback(error, null);
                    });
                }
            };
        },
        auth: {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
            },
        },
        enabledTransports: ['ws', 'wss'],
    });

    return window.Echo;
}

/**
 * Disconnect the WebSocket connection (on logout).
 */
export function disconnectSocket() {
    if (window.Echo) {
        try {
            window.Echo.disconnect();
        } catch (e) {
            console.warn('[WebSocket] Error during disconnect:', e);
        }
        window.Echo = null;
    }
}