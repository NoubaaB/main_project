import { defineStore } from "pinia";
import i18n from '@/plugins/i18n';

/**
 * Notification Store
 * ──────────────────
 * Reusable notification system for entity creation events.
 * Supports: charges, production, inventory, sells, and any future entity.
 *
 * Usage:
 *   const notification = useNotification();
 *   notification.notifyCreation({
 *     type: 'charge',                              // entity type key
 *     icon: 'mdi-warehouse',                        // MDI icon
 *     color: 'info',                                // Vuetify color
 *     fields: [
 *       { label: 'tracking_numbers', value: '12345' },
 *       { label: 'suppliers', value: 'Golden Logistics' },
 *       { label: 'total_price', value: '5,200.00 Dh', icon: 'mdi-cash' },
 *       { label: 'total_weight', value: '1,200 Kg', icon: 'mdi-weight-kilogram' },
 *     ]
 *   });
 */
export const useNotification = defineStore("notification", {
    state: () => ({
        // Stack of active notifications (supports multiple simultaneous toasts)
        notifications: [],
        // Auto-increment ID
        _nextId: 1,
        // Default duration in ms
        defaultDuration: 6000,
    }),
    actions: {
        /**
         * Generic notification push
         * @param {Object} opts
         * @param {string}   opts.title    – Notification title
         * @param {string}   opts.message  – Short description
         * @param {string}   opts.icon     – MDI icon name
         * @param {string}   opts.color    – Vuetify color (info, success, warning, error)
         * @param {Array}    opts.fields   – Array of { label, value, icon? } for detail rows
         * @param {number}   opts.duration – Auto-dismiss time in ms (0 = sticky)
         */
        push(opts = {}) {
            const id = this._nextId++;
            const notification = {
                id,
                title: opts.title || '',
                message: opts.message || '',
                icon: opts.icon || 'mdi-check-circle-outline',
                color: opts.color || 'info',
                fields: opts.fields || [],
                duration: opts.duration ?? this.defaultDuration,
                visible: true,
                createdAt: Date.now(),
            };
            this.notifications.push(notification);

            // Auto-dismiss after duration
            if (notification.duration > 0) {
                setTimeout(() => {
                    this.dismiss(id);
                }, notification.duration);
            }

            return id;
        },

        /**
         * Dismiss a notification by ID
         */
        dismiss(id) {
            const idx = this.notifications.findIndex(n => n.id === id);
            if (idx !== -1) {
                this.notifications[idx].visible = false;
                // Remove from DOM after transition
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 400);
            }
        },

        /**
         * Dismiss all notifications
         */
        dismissAll() {
            this.notifications.forEach(n => n.visible = false);
            setTimeout(() => {
                this.notifications = [];
            }, 400);
        },

        // ─── Convenience methods for entity creation notifications ───

        /**
         * Notify that an entity was created.
         * @param {Object}  opts
         * @param {string}  opts.type   – Entity type key (charge, production, inventory, sell)
         * @param {string}  opts.icon   – MDI icon
         * @param {string}  opts.color  – Vuetify color
         * @param {Array}   opts.fields – Detail fields [{ label, value, icon? }]
         */
        notifyCreation(opts = {}) {
            const typeLabel = i18n.global.t(opts.type || 'item');
            const title = i18n.global.t('entity_created_title', { entity: typeLabel });
            const message = i18n.global.t('entity_created_message', { entity: typeLabel });

            return this.push({
                title,
                message,
                icon: opts.icon || 'mdi-check-circle-outline',
                color: opts.color || 'success',
                fields: opts.fields || [],
                duration: opts.duration ?? 8000,
            });
        },

        /**
         * Notify that an entity was updated.
         */
        notifyUpdate(opts = {}) {
            const typeLabel = i18n.global.t(opts.type || 'item');
            const title = i18n.global.t('entity_updated_title', { entity: typeLabel });
            const message = i18n.global.t('entity_updated_message', { entity: typeLabel });

            return this.push({
                title,
                message,
                icon: opts.icon || 'mdi-pencil-check',
                color: opts.color || 'info',
                fields: opts.fields || [],
                duration: opts.duration ?? 6000,
            });
        },

        /**
         * Notify that an entity was deleted.
         */
        notifyDeletion(opts = {}) {
            const typeLabel = i18n.global.t(opts.type || 'item');
            const title = i18n.global.t('entity_deleted_title', { entity: typeLabel });

            return this.push({
                title,
                message: '',
                icon: opts.icon || 'mdi-delete-check',
                color: opts.color || 'error',
                fields: opts.fields || [],
                duration: opts.duration ?? 5000,
            });
        },
    },
});
