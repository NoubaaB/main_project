import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

/**
 * Resolve the optimal WebSocket configuration based on the current window location.
 * Seamlessly handles:
 *  1. Remote WLAN / Internet via Cloudflare Tunnel (*.congelationvillamar.com)
 *  2. Local WAN / LAN via Apache HTTPS (e.g. dev.local or local SSL)
 *  3. Local WAN / LAN via Apache HTTP (e.g. 192.168.1.160 or localhost)
 */
export function resolveSocketConfig() {
    const wsCfg = (typeof window !== 'undefined' && window.__WS_CONFIG__) || {};
    const hostname = (typeof window !== 'undefined' && window.location && window.location.hostname) || 'localhost';
    const protocol = (typeof window !== 'undefined' && window.location && window.location.protocol) || 'http:';
    const isHttps = protocol === 'https:';

    const appKey = wsCfg.appKey || import.meta.env.VITE_REVERB_APP_KEY;
    const tunnelHost = wsCfg.tunnelHost || import.meta.env.VITE_TUNNEL_REVERB_HOST || 'brotherhoodsocket.congelationvillamar.com';
    const tunnelDomain = wsCfg.tunnelWebDomain || import.meta.env.VITE_TUNNEL_DOMAIN || 'congelationvillamar.com';

    // 1. Internet Remote Access via Cloudflare Tunnel
    const isCloudflareTunnel = hostname.endsWith(tunnelDomain) || hostname.includes('congelationvillamar') || hostname.includes('brotherhood');
    if (isCloudflareTunnel) {
        return {
            mode: 'Cloudflare Tunnel (Internet)',
            key: appKey,
            wsHost: tunnelHost,
            wsPort: 443,
            wssPort: 443,
            forceTLS: true,
        };
    }

    // 2. Local Network (LAN) via HTTPS (dev.local or local domain with SSL)
    if (isHttps) {
        return {
            mode: 'Local Network (HTTPS)',
            key: appKey,
            wsHost: hostname, // dev.local, localhost, or LAN IP
            wsPort: 443,
            wssPort: 443,
            forceTLS: true,
        };
    }

    // 3. Local Network (LAN) via HTTP (e.g. 192.168.1.160, localhost)
    const port = (typeof window !== 'undefined' && window.location && window.location.port) ? parseInt(window.location.port) : 80;
    return {
        mode: 'Local Network (HTTP)',
        key: appKey,
        wsHost: hostname, // e.g. 192.168.1.160
        wsPort: port,
        wssPort: 443,
        forceTLS: false,
    };
}

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

    const config = resolveSocketConfig();
    console.log(`[WebSocket] Initializing Echo in [${config.mode}] mode:`, {
        host: config.wsHost,
        port: config.forceTLS ? config.wssPort : config.wsPort,
        tls: config.forceTLS,
        appKey: config.key ? config.key.substring(0, 6) + '...' : 'missing',
    });

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.wsHost,
        wsPort: config.wsPort,
        wssPort: config.wssPort,
        forceTLS: config.forceTLS,
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

    // Register connection status listeners
    if (window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;

        pusher.connection.bind('connected', () => {
            const socketId = pusher.connection.socket_id;
            console.log(`[WebSocket] Connected successfully (socket_id: ${socketId})`);
            window.dispatchEvent(new CustomEvent('websocket:status', { detail: { state: 'connected', socketId } }));
        });

        pusher.connection.bind('connecting', () => {
            console.log('[WebSocket] Connecting...');
            window.dispatchEvent(new CustomEvent('websocket:status', { detail: { state: 'connecting' } }));
        });

        pusher.connection.bind('disconnected', () => {
            console.warn('[WebSocket] Disconnected');
            window.dispatchEvent(new CustomEvent('websocket:status', { detail: { state: 'disconnected' } }));
        });

        pusher.connection.bind('error', (err) => {
            console.error('[WebSocket] Connection error:', err);
            window.dispatchEvent(new CustomEvent('websocket:status', { detail: { state: 'error', error: err } }));
        });
    }

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
