import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useAuth } from './Auth';
import { useMain } from './Main';
import i18n from '@/plugins/i18n';
import router from '@/router';

/**
 * Utility to convert URL-safe base64 string to Uint8Array for PushManager
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

export const useWebPush = defineStore('webPush', () => {
  /* ── State ────────────────────────────────────── */
  const isSupported    = ref(false);
  const permission     = ref('default'); // 'default' | 'granted' | 'denied'
  const isSubscribed   = ref(false);
  const deviceCount    = ref(0);
  const loading        = ref(false);
  const vapidPublicKey = ref(null);
  const registration   = ref(null);
  const currentSub     = ref(null);

  /* ── Getters ──────────────────────────────────── */
  const isEnabled = computed(() => permission.value === 'granted' && isSubscribed.value);
  const isBlocked = computed(() => permission.value === 'denied');
  const needsPermission = computed(() => permission.value === 'default');

  /* ── Actions ─────────────────────────────────── */

  /**
   * Initialize Web Push detection and current subscription state.
   */
  async function init() {
    if (typeof window === 'undefined') return;

    isSupported.value = (
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    );

    if (!isSupported.value) {
      console.log('[WebPush] Web push notifications not supported in this browser.');
      return;
    }

    permission.value = Notification.permission;

    // Listen for service worker navigation messages from notification clicks
    if (navigator.serviceWorker) {
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'NAVIGATE_TO' && event.data.url) {
          try {
            router.push(event.data.url).catch(() => {});
          } catch (e) {
            console.warn('[WebPush] Failed to navigate from notification message:', e);
          }
        }
      });
    }

    try {
      registration.value = await navigator.serviceWorker.ready;
      const sub = await registration.value.pushManager.getSubscription();

      if (sub) {
        currentSub.value = sub;
        isSubscribed.value = true;
      } else {
        isSubscribed.value = false;
      }

      // Check backend status if user is authenticated
      const auth = useAuth();
      if (auth.is_auth) {
        await checkBackendStatus();
      }
    } catch (err) {
      console.warn('[WebPush] Error during init:', err);
    }
  }

  /**
   * Check push subscription status from backend.
   */
  async function checkBackendStatus() {
    try {
      const { data } = await axios.get('/api/webpush/status');
      if (data && data.status) {
        deviceCount.value = data.device_count || 0;
      }
    } catch (err) {
      console.warn('[WebPush] checkBackendStatus failed:', err);
    }
  }

  /**
   * Fetch application VAPID public key.
   */
  async function fetchVapidKey() {
    if (vapidPublicKey.value) return vapidPublicKey.value;
    try {
      const { data } = await axios.get('/api/webpush/vapid-public-key');
      if (data && data.publicKey) {
        vapidPublicKey.value = data.publicKey;
        return data.publicKey;
      }
    } catch (err) {
      console.error('[WebPush] Failed to fetch VAPID key:', err);
    }
    return null;
  }

  /**
   * Request notification permission from user and subscribe to PushManager.
   */
  async function subscribe() {
    if (!isSupported.value) return false;
    loading.value = true;

    try {
      // Request permission
      const result = await Notification.requestPermission();
      permission.value = result;

      if (result !== 'granted') {
        const main = useMain();
        main.snackbar = {
          action_command: true,
          action_command_icon: 'mdi-bell-cancel-outline',
          action_command_icon_color: '#EF5350',
          action_command_text: i18n.global.t('push_notifications_denied') || 'Push notifications were blocked or not allowed.'
        };
        loading.value = false;
        return false;
      }

      // Get service worker ready
      const reg = registration.value || (await navigator.serviceWorker.ready);
      registration.value = reg;

      // Fetch VAPID key
      const key = await fetchVapidKey();
      if (!key) {
        throw new Error('Unable to retrieve VAPID public key from server');
      }

      const convertedKey = urlBase64ToUint8Array(key);

      // Subscribe to PushManager
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: convertedKey,
        });
      }

      currentSub.value = sub;

      // Extract keys
      const p256dh = sub.getKey('p256dh')
        ? btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('p256dh'))))
        : '';
      const authSecret = sub.getKey('auth')
        ? btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('auth'))))
        : '';

      // Register with backend
      await axios.post('/api/webpush/subscribe', {
        endpoint: sub.endpoint,
        keys: {
          p256dh: p256dh,
          auth: authSecret,
        },
        content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0] || 'aes128gcm',
      });

      isSubscribed.value = true;
      deviceCount.value++;

      const main = useMain();
      main.snackbar = {
        action_command: true,
        action_command_icon: 'mdi-bell-check-outline',
        action_command_icon_color: '#4CAF50',
        action_command_text: i18n.global.t('push_notifications_enabled') || 'Web Push Notifications successfully enabled!'
      };

      return true;
    } catch (err) {
      console.error('[WebPush] Subscription error:', err);
      const main = useMain();
      main.snackbar = {
        action_command: true,
        action_command_icon: 'mdi-alert-circle-outline',
        action_command_icon_color: '#F44336',
        action_command_text: (i18n.global.t('push_notifications_error') || 'Failed to enable push notifications: ') + err.message
      };
      return false;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Unsubscribe from push notifications.
   */
  async function unsubscribe() {
    if (!isSupported.value) return false;
    loading.value = true;

    try {
      const reg = registration.value || (await navigator.serviceWorker.ready);
      const sub = await reg.pushManager.getSubscription();

      if (sub) {
        // Notify backend first
        try {
          await axios.post('/api/webpush/unsubscribe', {
            endpoint: sub.endpoint,
          });
        } catch (e) {
          console.warn('[WebPush] Unsubscribe backend call warning:', e);
        }

        // Unsubscribe from browser push manager
        await sub.unsubscribe();
      }

      isSubscribed.value = false;
      currentSub.value = null;
      deviceCount.value = Math.max(0, deviceCount.value - 1);

      const main = useMain();
      main.snackbar = {
        action_command: true,
        action_command_icon: 'mdi-bell-off-outline',
        action_command_icon_color: '#FF9800',
        action_command_text: i18n.global.t('push_notifications_disabled') || 'Web Push Notifications disabled.'
      };

      return true;
    } catch (err) {
      console.error('[WebPush] Unsubscribe error:', err);
      return false;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Trigger an instant test Web Push notification.
   */
  async function sendTestNotification() {
    loading.value = true;
    try {
      const { data } = await axios.post('/api/webpush/test');
      const main = useMain();
      main.snackbar = {
        action_command: true,
        action_command_icon: 'mdi-bell-ring-outline',
        action_command_icon_color: '#42A5F5',
        action_command_text: data.message || 'Test push notification sent!'
      };
      return true;
    } catch (err) {
      console.error('[WebPush] Test notification error:', err);
      const main = useMain();
      main.snackbar = {
        action_command: true,
        action_command_icon: 'mdi-alert-circle-outline',
        action_command_icon_color: '#F44336',
        action_command_text: i18n.global.t('push_test_failed') || 'Failed to dispatch test notification'
      };
      return false;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Display a local desktop notification (used when WebSocket event arrives in active browser session).
   */
  async function showDesktopNotification(title, options = {}) {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;

    const defaultOptions = {
      icon: '/images/jupiter-logo.png',
      badge: '/favicon.ico',
      vibrate: [100, 50, 100],
      tag: 'jupiter-notification-' + Date.now(),
      renotify: true,
      ...options,
    };

    // Use ServiceWorkerRegistration.showNotification if available
    try {
      const reg = registration.value || (await navigator.serviceWorker.ready);
      if (reg && reg.showNotification) {
        await reg.showNotification(title, defaultOptions);
        return;
      }
    } catch (e) {
      // Fall back to standard Notification constructor
    }

    try {
      const n = new Notification(title, defaultOptions);
      n.onclick = (e) => {
        e.preventDefault();
        window.focus();
        if (defaultOptions.data && defaultOptions.data.url) {
          router.push(defaultOptions.data.url).catch(() => {});
        }
        n.close();
      };
    } catch (e) {
      console.warn('[WebPush] Could not create local notification:', e);
    }
  }

  /**
   * Convenience helper to notify a model change event from WebSocket.
   */
  function notifyModelChange(eventData) {
    if (permission.value !== 'granted') return;

    const auth = useAuth();
    if (eventData.actor_id && auth.user && eventData.actor_id === auth.user.id) {
      return; // Never notify actor
    }

    if (eventData.permission_key && !auth.hasPermission(eventData.permission_key)) {
      return; // No permission for this module
    }

    const action = eventData.action || 'created';
    const model = eventData.model || 'Record';
    const actor = eventData.user_name || 'A user';
    const data = eventData.record_data || {};

    let details = '';
    if (data.tracking) details += `Ref: ${data.tracking} `;
    if (data.reception_tracking_number) {
      const trk = Array.isArray(data.reception_tracking_number) ? data.reception_tracking_number.join(', ') : data.reception_tracking_number;
      details += `Ref: ${trk} `;
    }
    if (data.weight || data.total_weight) {
      details += `• ${data.weight || data.total_weight} kg `;
    }
    if (data.price || data.total_price) {
      details += `• ${data.price || data.total_price} MAD `;
    }

    const title = `Jupiter ERP • ${model} ${action}`;
    const body = `${actor} ${action} a ${model} record ${details}`.trim();

    // Map to url
    const urlMap = {
      Charge: `/view_charge/${eventData.record_id}`,
      Production: `/view_production/${eventData.record_id}`,
      Shipping: `/view_shipping/${eventData.record_id}`,
      Inventory: `/edit_inventory/${eventData.record_id}`,
      WarehouseTransfer: `/edit_warehouse_transfer/${eventData.record_id}`,
      SupplierInvoice: '/list_supplier_invoice',
      ClientInvoice: '/list_client_invoice',
      StorageInvoice: '/storage_invoices',
      OrderTransfer: '/order_transfers_page',
      FortnightActivity: '/fortnight_management',
      CostConfiguration: '/cost_configuration_management',
    };

    const targetUrl = urlMap[model] || '/';

    showDesktopNotification(title, {
      body,
      tag: `jupiter-${model}-${eventData.record_id}`,
      data: {
        url: targetUrl,
        model,
        action,
        record_id: eventData.record_id
      }
    });
  }

  return {
    // State
    isSupported,
    permission,
    isSubscribed,
    deviceCount,
    loading,
    vapidPublicKey,
    registration,
    // Getters
    isEnabled,
    isBlocked,
    needsPermission,
    // Actions
    init,
    checkBackendStatus,
    fetchVapidKey,
    subscribe,
    unsubscribe,
    sendTestNotification,
    showDesktopNotification,
    notifyModelChange,
  };
});
