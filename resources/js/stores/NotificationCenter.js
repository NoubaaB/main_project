import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useAuth } from './Auth';

export const useNotificationCenter = defineStore('notificationCenter', () => {
  /* ── State ────────────────────────────────────── */
  const notifications = ref([]);
  const unreadCount   = ref(0);
  const loading       = ref(false);
  const hasMore       = ref(true);
  const currentPage   = ref(1);

  /* ── Module → Route mapping for clickable notifications ── */
  const routeMap = {
    Charge:             { name: 'view_charge',              paramKey: 'id' },
    Production:         { name: 'view_production',          paramKey: 'id' },
    Shipping:           { name: 'view_shipping',            paramKey: 'id' },
    Inventory:          { name: 'edit_inventory',            paramKey: 'id' },
    WarehouseTransfer:  { name: 'edit_warehouse_transfer',   paramKey: 'id' },
    SupplierInvoice:    { name: 'list_supplier_invoice',     paramKey: null },
    ClientInvoice:      { name: 'list_client_invoice',       paramKey: null },
    StorageInvoice:     { name: 'storage_invoices',          paramKey: null },
    OrderTransfer:      { name: 'order_transfers_page',      paramKey: null },
    FortnightActivity:  { name: 'fortnight_management',      paramKey: null },
  };

  /* ── Module → Color mapping from claude.md design system ── */
  const colorMap = {
    Charge:             '#42A5F5',
    Production:         '#00897B',
    Shipping:           '#FB8C00',
    Inventory:          '#8D6E63',
    WarehouseTransfer:  '#78909C',
    SupplierInvoice:    '#5C6BC0',
    ClientInvoice:      '#26A69A',
    StorageInvoice:     '#7E57C2',
    OrderTransfer:      '#EF5350',
    FortnightActivity:  '#66BB6A',
  };

  /* ── Module → Icon mapping ── */
  const iconMap = {
    Charge:             'mdi-truck-delivery-outline',
    Production:         'mdi-factory',
    Shipping:           'mdi-package-variant-closed',
    Inventory:          'mdi-clipboard-check-outline',
    WarehouseTransfer:  'mdi-swap-horizontal',
    SupplierInvoice:    'mdi-file-document-outline',
    ClientInvoice:      'mdi-receipt-text-outline',
    StorageInvoice:     'mdi-warehouse',
    OrderTransfer:      'mdi-transfer',
    FortnightActivity:  'mdi-account-hard-hat',
  };

  /* ── Module → i18n key mapping ── */
  const i18nKeyMap = {
    Charge:             'notification_charge',
    Production:         'notification_production',
    Shipping:           'notification_shipping',
    Inventory:          'notification_inventory',
    WarehouseTransfer:  'notification_warehouse_transfer',
    SupplierInvoice:    'notification_supplier_invoice',
    ClientInvoice:      'notification_client_invoice',
    StorageInvoice:     'notification_storage_invoice',
    OrderTransfer:      'notification_order_transfer',
    FortnightActivity:  'notification_fortnight_activity',
  };

  /* ── Actions ─────────────────────────────────── */

  async function fetchNotifications(page = 1) {
    loading.value = true;
    try {
      const { data } = await axios.get('/api/notifications', {
        params: { per_page: 50, page }
      });
      if (page === 1) {
        notifications.value = data.data || [];
      } else {
        notifications.value.push(...(data.data || []));
      }
      currentPage.value = data.current_page || page;
      hasMore.value = data.current_page < data.last_page;
    } catch (err) {
      console.warn('[NotificationCenter] fetchNotifications failed:', err);
    } finally {
      loading.value = false;
    }
  }

  async function fetchUnreadCount() {
    try {
      const { data } = await axios.get('/api/notifications/unread-count');
      unreadCount.value = data.count || 0;
    } catch (err) {
      console.warn('[NotificationCenter] fetchUnreadCount failed:', err);
    }
  }

  async function markAsRead(id) {
    try {
      await axios.put(`/api/notifications/${id}/read`);
      const n = notifications.value.find(n => n.id === id);
      if (n) n.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (err) {
      console.warn('[NotificationCenter] markAsRead failed:', err);
    }
  }

  async function markAllAsRead() {
    try {
      await axios.put('/api/notifications/read-all');
      notifications.value.forEach(n => {
        if (!n.read_at) n.read_at = new Date().toISOString();
      });
      unreadCount.value = 0;
    } catch (err) {
      console.warn('[NotificationCenter] markAllAsRead failed:', err);
    }
  }

  /**
   * Handle a real-time WebSocket notification.
   * Called from OnlineUsers store when a model change event arrives.
   */
  function handleRealtimeNotification(eventData) {
    const auth = useAuth();

    // Don't notify the actor
    if (eventData.actor_id === auth.user?.id) return;

    // Check if user has permission
    if (eventData.permission_key && !auth.hasPermission(eventData.permission_key)) return;

    // Build a notification object matching the DB format
    const notification = {
      id: `rt_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
      user_id: auth.user?.id,
      type: eventData.model,
      action: eventData.action,
      record_id: eventData.record_id,
      data: eventData.record_data || {},
      actor_name: eventData.user_name,
      actor_id: eventData.actor_id,
      permission_key: eventData.permission_key,
      read_at: null,
      created_at: eventData.timestamp || new Date().toISOString(),
    };

    // Prepend to the list
    notifications.value.unshift(notification);

    // Cap at 100 in memory
    if (notifications.value.length > 100) {
      notifications.value = notifications.value.slice(0, 100);
    }

    // Increment unread count
    unreadCount.value++;
  }

  /**
   * Get the router location for a notification click.
   */
  function getRouteForNotification(notification) {
    const route = routeMap[notification.type];
    if (!route) return null;
    if (route.paramKey && notification.record_id) {
      return { name: route.name, params: { [route.paramKey]: notification.record_id } };
    }
    return { name: route.name };
  }

  function getColorForModule(type) {
    return colorMap[type] || '#78909C';
  }

  function getIconForModule(type) {
    return iconMap[type] || 'mdi-bell-outline';
  }

  function getI18nKeyForModule(type) {
    return i18nKeyMap[type] || type;
  }

  /**
   * Get a relative time string from an ISO date.
   */
  function getRelativeTime(dateStr, t) {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1)  return t('just_now');
    if (diffMin < 60) return t('minutes_ago', { count: diffMin });
    if (diffHrs < 24) return t('hours_ago',   { count: diffHrs });
    return t('days_ago', { count: diffDays });
  }

  /* ── Init ────────────────────────────────────── */
  function init() {
    fetchNotifications(1);
    fetchUnreadCount();
  }

  return {
    // State
    notifications,
    unreadCount,
    loading,
    hasMore,
    currentPage,
    // Actions
    init,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    handleRealtimeNotification,
    getRouteForNotification,
    getColorForModule,
    getIconForModule,
    getI18nKeyForModule,
    getRelativeTime,
  };
});
