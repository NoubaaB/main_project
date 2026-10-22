import { defineStore } from 'pinia';
import { initSocket, disconnectSocket } from '@/echo.js';
import mitt from '@/plugins/mitt.js';

// Import all stores that can be updated via WebSocket
import { useAuth } from '@/stores/Auth';
import { useSupplier } from '@/stores/Supplier';
import { useShip } from '@/stores/Ship';
import { useClient } from '@/stores/Client';
import { useTruck } from '@/stores/Truck';
import { useFishType } from '@/stores/FishType';
import { useFishFamily } from '@/stores/FishFamily';
import { useFishProcessType } from '@/stores/FishProcessType';
import { usePackageType } from '@/stores/PackageType';
import { useGuanoType } from '@/stores/GuanoType';
import { useFishery } from '@/stores/Fishery';
import { useOrigin } from '@/stores/Origin';
import { useWarehouse } from '@/stores/Warehouse';
import { useProductionCategory } from '@/stores/ProductionCategory';
import { useChargeCategory } from '@/stores/ChargeCategory';
import { useShippingCategory } from '@/stores/ShippingCategory';
import { useDetailChargeCategory } from '@/stores/DetailChargeCategory';
import { useDetailProductionCategory } from '@/stores/DetailProductionCategory';
import { useDetailShippingCategory } from '@/stores/DetailShippingCategory';
import { useFormula } from '@/stores/Formula';
import { useCorporal } from '@/stores/Corporal';
import { useSection } from '@/stores/Section';
import { useWorkforce } from '@/stores/Workforce';
import { useTeam } from '@/stores/Team';
import { useDetailInventoryCategory } from '@/stores/DetailInventoryCategory';
import { useCompanySettings } from '@/stores/CompanySettings';
import { useFortnightSetting } from '@/stores/FortnightSetting';
import { useCustomChart } from '@/stores/CustomChart';
import { useStaticCharge } from '@/stores/StaticCharge';
import { useNotificationCenter } from '@/stores/NotificationCenter';
import { useWorkforceMovement } from '@/stores/WorkforceMovement';
import { useWebPush } from '@/stores/WebPush';

export const useOnlineUsers = defineStore('OnlineUsers', {
    state: () => ({
        users: [],
        snackbar: {
            show: false,
            text: '',
            color: 'success',
        },
        _presenceChannel: null,
        _modelChannel: null,
        _connected: false,
    }),
    getters: {
        /** Other online users (excludes the authenticated user) */
        otherUsers(state) {
            const auth = useAuth();
            const authId = auth.user?.id;
            return authId ? state.users.filter(u => u.id !== authId) : state.users;
        },
        onlineCount() { return this.otherUsers.length; },
        isConnected: (state) => state._connected,
    },
    actions: {
        connect() {
            if (this._connected) return;

            try {
                initSocket();
            } catch (e) {
                console.warn('[WebSocket] Failed to init socket:', e);
                return;
            }

            if (!window.Echo) {
                console.warn('[WebSocket] Echo not available');
                return;
            }

            this._presenceChannel = window.Echo.join('online-users')
                .here((users) => {
                    this.users = users;
                })
                .joining((user) => {
                    if (!this.users.find(u => u.id === user.id)) {
                        this.users.push(user);
                    }
                    this.showSnackbar(`${user.name} connected`, 'success');
                })
                .leaving((user) => {
                    this.users = this.users.filter(u => u.id !== user.id);
                    this.showSnackbar(`${user.name} disconnected`, 'warning');
                })
                .error((error) => {
                    console.error('[WebSocket] Presence channel error:', error);
                });

            this._modelChannel = window.Echo.channel('model-changes')
                .listen('.model.changed', (data) => {
                    this.handleModelChange(data);
                });

            this._connected = true;
        },

        disconnect() {
            if (window.Echo) {
                if (this._presenceChannel) {
                    window.Echo.leave('online-users');
                    this._presenceChannel = null;
                }
                if (this._modelChannel) {
                    window.Echo.leave('model-changes');
                    this._modelChannel = null;
                }
            }
            disconnectSocket();
            this.users = [];
            this._connected = false;
        },

        /**
         * Handle incoming model change events.
         * 1) Show snackbar notification
         * 2) Update the matching Pinia store list reactively
         * 3) Emit mitt bus event so DataTable pages (Charge, Sell) re-fetch
         */
        handleModelChange(data) {
            const auth = useAuth();
            const isOwnAction = data.actor_id && auth.user && data.actor_id === auth.user.id;

            // Only show snackbar + notification to OTHER users, not the actor
            if (!isOwnAction) {
                const actionLabels = { created: 'created', updated: 'updated', deleted: 'deleted' };
                const actionLabel = actionLabels[data.action] || data.action;
                this.showSnackbar(`${data.user_name} ${actionLabel} a ${data.model} record`, 'info');

                // Push to NotificationCenter for real-time notifications
                try {
                    const notifCenter = useNotificationCenter();
                    notifCenter.handleRealtimeNotification(data);
                } catch (e) {
                    console.warn('[WebSocket] Failed to push notification:', e);
                }

                // Trigger Web Push Notification / Desktop Notification
                try {
                    const webPush = useWebPush();
                    webPush.notifyModelChange(data);
                } catch (e) {
                    console.warn('[WebPush] Failed to trigger desktop notification:', e);
                }
            }

            // Always update local store list for ALL users (keeps data in sync)
            this.updateLocalStore(data);

            // Emit event for DataTable-based pages (Charge, Sell, etc.) to re-fetch
            mitt.emit('model-changed', data);
        },

        /**
         * Reactively update the Pinia store list.
         */
        updateLocalStore(data) {
            const { model, action, record_id, record_data } = data;

            // If record_id is 0 or missing with empty record_data (AI Assistant bulk operation),
            // try to do a full store refresh for stores that support fetch()
            const isAiBulk = !record_id || (record_id === 0 && (!record_data || (typeof record_data === 'object' && Object.keys(record_data).length === 0)));
            if (isAiBulk) {
                const fetchableStores = {
                    'Corporal': () => useCorporal(),
                    'Section': () => useSection(),
                    'Workforce': () => useWorkforce(),
                    'Team': () => useTeam(),
                    'WorkforceMovement': () => useWorkforceMovement(),
                };
                if (fetchableStores[model]) {
                    try {
                        if (fetchableStores[model]().fetch) fetchableStores[model]().fetch();
                        if (fetchableStores[model]().fetchAbsences) fetchableStores[model]().fetchAbsences();
                    } catch (e) { /* ignore */ }
                }
                return;
            }

            // Stores that need full re-fetch (have relationships loaded via fetch())
            const fetchOnChange = ['Corporal', 'Section', 'Workforce', 'Team', 'WorkforceMovement'];

            // Map model names to their store instances
            const storeMap = {
                'Supplier': () => useSupplier(),
                'Ship': () => useShip(),
                'Client': () => useClient(),
                'Truck': () => useTruck(),
                'FishType': () => useFishType(),
                'FishFamily': () => useFishFamily(),
                'FishProcessType': () => useFishProcessType(),
                'PackageType': () => usePackageType(),
                'GuanoType': () => useGuanoType(),
                'Fishery': () => useFishery(),
                'Origin': () => useOrigin(),
                'Warehouse': () => useWarehouse(),
                'ProductionCategory': () => useProductionCategory(),
                'ChargeCategory': () => useChargeCategory(),
                'ShippingCategory': () => useShippingCategory(),
                'DetailChargeCategory': () => useDetailChargeCategory(),
                'DetailProductionCategory': () => useDetailProductionCategory(),
                'DetailShippingCategory': () => useDetailShippingCategory(),
                'DetailInventoryCategory': () => useDetailInventoryCategory(),
                'Formula': () => useFormula(),
                'Corporal': () => useCorporal(),
                'Section': () => useSection(),
                'Workforce': () => useWorkforce(),
                'Team': () => useTeam(),
                'WorkforceMovement': () => useWorkforceMovement(),
            };

            // CompanySetting is a key-value store, handle separately
            if (model === 'CompanySetting') {
                try {
                    const csStore = useCompanySettings();
                    if (record_data && typeof record_data === 'object') {
                        csStore.setSettings(record_data);
                    } else {
                        // Full refresh if no inline data
                        csStore.fetchSettings();
                    }
                } catch (e) {
                    console.warn('[WebSocket] Failed to update CompanySettings:', e);
                }
                return;
            }

            // FortnightSetting uses a list-based store with CRUD
            if (model === 'FortnightSetting') {
                try {
                    const fsStore = useFortnightSetting();
                    fsStore.handleWsUpdate({ action, id: record_id, data: record_data });
                } catch (e) {
                    console.warn('[WebSocket] Failed to update FortnightSetting:', e);
                }
                return;
            }

            // CustomChart uses same list-based pattern
            if (model === 'CustomChart') {
                try {
                    const ccStore = useCustomChart();
                    ccStore.handleWsUpdate({ action, id: record_id, data: record_data });
                } catch (e) {
                    console.warn('[WebSocket] Failed to update CustomChart:', e);
                }
                return;
            }

            // StaticCharge uses same list-based pattern
            if (model === 'StaticCharge') {
                try {
                    const scStore = useStaticCharge();
                    scStore.handleWsUpdate({ action, id: record_id, data: record_data });
                } catch (e) {
                    console.warn('[WebSocket] Failed to update StaticCharge:', e);
                }
                return;
            }

            const factory = storeMap[model];
            if (!factory) return;

            let store;
            try { store = factory(); } catch (e) { return; }

            // For stores with relationships, do a full re-fetch
            if (fetchOnChange.includes(model)) {
                if (typeof store.fetch === 'function') {
                    try { store.fetch(); } catch (e) { /* ignore */ }
                }
                if (typeof store.fetchAbsences === 'function') {
                    try { store.fetchAbsences(); } catch (e) { /* ignore */ }
                }
                // If workforce changed, also refresh workforce movement absences
                if (model === 'Workforce') {
                    try { useWorkforceMovement().fetchAbsences(); } catch (e) { /* ignore */ }
                }
                return;
            }

            if (!record_data || !Array.isArray(store.list)) return;

            try {
                if (action === 'created') {
                    if (!store.list.find(item => item.id === record_id)) {
                        store.list.push(record_data);
                    }
                } else if (action === 'updated') {
                    const idx = store.list.findIndex(item => item.id === record_id);
                    if (idx !== -1) {
                        store.list[idx] = { ...store.list[idx], ...record_data };
                    }
                } else if (action === 'deleted') {
                    store.list = store.list.filter(item => item.id !== record_id);
                }
            } catch (e) {
                console.warn('[WebSocket] Failed to update local store:', e);
            }
        },

        showSnackbar(text, color = 'info') {
            this.snackbar = { show: true, text, color };
        },

        dismissSnackbar() {
            this.snackbar.show = false;
        },
    },
});
