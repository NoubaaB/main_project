import { defineStore } from "pinia";
import axios from "axios";
import { useMain } from "./Main";
import { useAuth } from "./Auth";
import { useNotification } from "./Notification";
import { useSupplier } from "./Supplier";
import { useClient } from "./Client";
import { useChargeCategory } from "./ChargeCategory";
import { useSellCategory } from "./SellCategory";
import { useProductionCategory } from "./ProductionCategory";
import i18n from '@/plugins/i18n';

/**
 * Archive Store
 * ─────────────
 * Centralized per-user archive system for Charges, Sells, and Productions.
 * Archives are local draft snapshots stored in localStorage keyed by user ID.
 * Users can edit, submit (POST to API), or delete archived items.
 */
export const useArchive = defineStore("archive", {
    state: () => ({
        charges: [],
        sells: [],
        productions: [],
        _userId: null,
    }),
    actions: {
        // ─── Storage keys ───
        _key(type) {
            return `archive_${type}_${this._userId}`;
        },

        // ─── Load archives for the current user ───
        loadForUser(userId) {
            this._userId = userId;
            try {
                const charges = localStorage.getItem(this._key('charges'));
                const sells = localStorage.getItem(this._key('sells'));
                const productions = localStorage.getItem(this._key('productions'));
                this.charges = charges ? JSON.parse(charges) : [];
                this.sells = sells ? JSON.parse(sells) : [];
                this.productions = productions ? JSON.parse(productions) : [];
            } catch (e) {
                console.error("Error loading archives from localStorage:", e);
                this.charges = [];
                this.sells = [];
                this.productions = [];
            }
        },

        // ─── Persist to localStorage ───
        _save() {
            if (!this._userId) return;
            try {
                localStorage.setItem(this._key('charges'), JSON.stringify(this.charges));
                localStorage.setItem(this._key('sells'), JSON.stringify(this.sells));
                localStorage.setItem(this._key('productions'), JSON.stringify(this.productions));
            } catch (e) {
                console.error("Error saving archives to localStorage:", e);
            }
        },

        // ─── Build notification fields helper ───
        _buildChargeFields(item) {
            const main = useMain();
            const supplier = useSupplier();
            const fields = [];
            if (item.date) {
                fields.push({ label: i18n.global.t('date'), value: main.formateDate(item.date), icon: 'mdi-calendar' });
            }
            if (item.reception_tracking_number?.length) {
                fields.push({ label: i18n.global.t('reception_tracking_number'), value: item.reception_tracking_number.join(', '), icon: 'mdi-barcode' });
            }
            if (item.production_tracking_numbers?.length) {
                fields.push({ label: i18n.global.t('production_tracking_numbers'), value: item.production_tracking_numbers.join(', '), icon: 'mdi-factory' });
            }
            const supplierNames = (item.supplier_ids || [])
                .map(id => supplier.list.find(s => s.id === id)?.name)
                .filter(Boolean);
            if (supplierNames.length) {
                fields.push({ label: i18n.global.t('suppliers'), value: supplierNames.join(', '), icon: 'mdi-truck-delivery' });
            }
            if (item.total_weight) {
                fields.push({ label: i18n.global.t('total_weight'), value: main.formatDisplayNumber(item.total_weight) + ' Kg', icon: 'mdi-weight-kilogram' });
            }
            if (item.total_price) {
                fields.push({ label: i18n.global.t('total_price'), value: main.formatDisplayNumber(item.total_price) + ' ' + main.currency, icon: 'mdi-cash' });
            }
            return fields;
        },
        _buildSellFields(item) {
            const main = useMain();
            const client = useClient();
            const fields = [];
            if (item.date) {
                fields.push({ label: i18n.global.t('date'), value: main.formateDate(item.date), icon: 'mdi-calendar' });
            }
            if (item.tracking_numbers?.length) {
                fields.push({ label: i18n.global.t('tracking_numbers'), value: item.tracking_numbers.join(', '), icon: 'mdi-barcode' });
            }
            const clientNames = (item.client_ids || [])
                .map(id => client.list.find(c => c.id === id)?.name)
                .filter(Boolean);
            if (clientNames.length) {
                fields.push({ label: i18n.global.t('clients'), value: clientNames.join(', '), icon: 'mdi-account-group' });
            }
            if (item.total_weight) {
                fields.push({ label: i18n.global.t('total_weight'), value: main.formatDisplayNumber(item.total_weight) + ' Kg', icon: 'mdi-weight-kilogram' });
            }
            if (item.total_price) {
                fields.push({ label: i18n.global.t('total_price'), value: main.formatDisplayNumber(item.total_price) + ' ' + main.currency, icon: 'mdi-cash' });
            }
            return fields;
        },
        _buildProductionFields(item) {
            const main = useMain();
            const supplier = useSupplier();
            const fields = [];
            if (item.date) {
                fields.push({ label: i18n.global.t('date'), value: main.formateDate(item.date), icon: 'mdi-calendar' });
            }
            if (item.reception_tracking_number?.length) {
                fields.push({ label: i18n.global.t('reception_tracking_number'), value: item.reception_tracking_number.join(', '), icon: 'mdi-barcode' });
            }
            if (item.production_tracking_numbers?.length) {
                fields.push({ label: i18n.global.t('production_tracking_numbers'), value: item.production_tracking_numbers.join(', '), icon: 'mdi-factory' });
            }
            const supplierNames = (item.supplier_ids || [])
                .map(id => supplier.list.find(s => s.id === id)?.name)
                .filter(Boolean);
            if (supplierNames.length) {
                fields.push({ label: i18n.global.t('suppliers'), value: supplierNames.join(', '), icon: 'mdi-truck-delivery' });
            }
            if (item.total_weight) {
                fields.push({ label: i18n.global.t('total_weight'), value: main.formatDisplayNumber(item.total_weight) + ' Kg', icon: 'mdi-weight-kilogram' });
            }
            if (item.total_price) {
                fields.push({ label: i18n.global.t('total_price'), value: main.formatDisplayNumber(item.total_price) + ' ' + main.currency, icon: 'mdi-cash' });
            }
            return fields;
        },

        // ═══════════════════════════════════════
        //  CHARGE ARCHIVES
        // ═══════════════════════════════════════
        archiveCharge(chargeData) {
            const snapshot = JSON.parse(JSON.stringify(chargeData));
            snapshot._archivedAt = new Date().toISOString();
            this.charges.push(snapshot);
            this._save();

            const main = useMain();
            main.add_snackbar({
                show: true,
                message: i18n.global.t("charge_archived_successfully"),
                color: "success"
            });
            const notification = useNotification();
            notification.notifyCreation({
                type: 'archives',
                icon: 'mdi-archive-arrow-down',
                color: 'teal',
                fields: this._buildChargeFields(snapshot)
            });
        },
        removeCharge(id) {
            const item = this.charges.find(c => c.id === id);
            this.charges = this.charges.filter(c => c.id !== id);
            this._save();
            const main = useMain();
            main.add_snackbar({
                show: true,
                message: i18n.global.t("archive_deleted_successfully"),
                color: "success"
            });
        },
        removeCharges(ids) {
            const removed = this.charges.filter(c => ids.includes(c.id));
            this.charges = this.charges.filter(c => !ids.includes(c.id));
            this._save();
            const main = useMain();
            main.add_snackbar({
                show: true,
                message: i18n.global.t("archives_deleted_successfully", { count: removed.length }),
                color: "success"
            });
        },
        async submitCharge(id) {
            const item = this.charges.find(c => c.id === id);
            if (!item) return;
            try {
                const res = await axios.post("/api/charge", item);
                this.charges = this.charges.filter(c => c.id !== id);
                this._save();
                const main = useMain();
                const notification = useNotification();
                notification.notifyCreation({
                    type: 'charges',
                    icon: 'mdi-warehouse',
                    color: 'info',
                    fields: this._buildChargeFields(item)
                });
                main.add_snackbar({
                    show: true,
                    message: i18n.global.t("archive_submitted_successfully"),
                    color: "success"
                });
                return res.data;
            } catch (e) {
                console.error("Error submitting archived charge:", e);
                const main = useMain();
                main.add_snackbar({
                    show: true,
                    message: e.response?.data?.message || i18n.global.t("archive_submit_failed"),
                    color: "error"
                });
                return null;
            }
        },
        async submitCharges(ids) {
            const items = this.charges.filter(c => ids.includes(c.id));
            let successCount = 0;
            for (const item of items) {
                try {
                    await axios.post("/api/charge", item);
                    this.charges = this.charges.filter(c => c.id !== item.id);
                    successCount++;
                } catch (e) {
                    console.error("Error submitting archived charge:", e);
                }
            }
            this._save();
            const main = useMain();
            if (successCount > 0) {
                const allFields = items.flatMap(item => this._buildChargeFields(item));
                const notification = useNotification();
                notification.notifyCreation({
                    type: 'charges',
                    icon: 'mdi-warehouse',
                    color: 'info',
                    fields: allFields.slice(0, 6)
                });
                main.add_snackbar({
                    show: true,
                    message: i18n.global.t("archives_submitted_successfully", { count: successCount }),
                    color: "success"
                });
            }
        },

        // ═══════════════════════════════════════
        //  SELL ARCHIVES
        // ═══════════════════════════════════════
        archiveSell(sellData) {
            const snapshot = JSON.parse(JSON.stringify(sellData));
            snapshot._archivedAt = new Date().toISOString();
            this.sells.push(snapshot);
            this._save();

            const main = useMain();
            main.add_snackbar({
                show: true,
                message: i18n.global.t("sell_archived_successfully"),
                color: "success"
            });
            const notification = useNotification();
            notification.notifyCreation({
                type: 'archives',
                icon: 'mdi-archive-arrow-down',
                color: 'teal',
                fields: this._buildSellFields(snapshot)
            });
        },
        removeSell(id) {
            this.sells = this.sells.filter(s => s.id !== id);
            this._save();
            const main = useMain();
            main.add_snackbar({ show: true, message: i18n.global.t("archive_deleted_successfully"), color: "success" });
        },
        removeSells(ids) {
            const removed = this.sells.filter(s => ids.includes(s.id));
            this.sells = this.sells.filter(s => !ids.includes(s.id));
            this._save();
            const main = useMain();
            main.add_snackbar({ show: true, message: i18n.global.t("archives_deleted_successfully", { count: removed.length }), color: "success" });
        },
        async submitSell(id) {
            const item = this.sells.find(s => s.id === id);
            if (!item) return;
            try {
                const res = await axios.post("/api/sell", item);
                this.sells = this.sells.filter(s => s.id !== id);
                this._save();
                const main = useMain();
                const notification = useNotification();
                notification.notifyCreation({ type: 'sells', icon: 'mdi-cart-check', color: 'green', fields: this._buildSellFields(item) });
                main.add_snackbar({ show: true, message: i18n.global.t("archive_submitted_successfully"), color: "success" });
                return res.data;
            } catch (e) {
                console.error("Error submitting archived sell:", e);
                const main = useMain();
                main.add_snackbar({ show: true, message: e.response?.data?.message || i18n.global.t("archive_submit_failed"), color: "error" });
                return null;
            }
        },
        async submitSells(ids) {
            const items = this.sells.filter(s => ids.includes(s.id));
            let successCount = 0;
            for (const item of items) {
                try {
                    await axios.post("/api/sell", item);
                    this.sells = this.sells.filter(s => s.id !== item.id);
                    successCount++;
                } catch (e) { console.error("Error submitting archived sell:", e); }
            }
            this._save();
            if (successCount > 0) {
                const main = useMain();
                const notification = useNotification();
                notification.notifyCreation({ type: 'sells', icon: 'mdi-cart-check', color: 'green', fields: items.flatMap(i => this._buildSellFields(i)).slice(0, 6) });
                main.add_snackbar({ show: true, message: i18n.global.t("archives_submitted_successfully", { count: successCount }), color: "success" });
            }
        },

        // ═══════════════════════════════════════
        //  PRODUCTION ARCHIVES
        // ═══════════════════════════════════════
        archiveProduction(prodData) {
            const snapshot = JSON.parse(JSON.stringify(prodData));
            snapshot._archivedAt = new Date().toISOString();
            this.productions.push(snapshot);
            this._save();

            const main = useMain();
            main.add_snackbar({
                show: true,
                message: i18n.global.t("production_archived_successfully"),
                color: "success"
            });
            const notification = useNotification();
            notification.notifyCreation({
                type: 'archives',
                icon: 'mdi-archive-arrow-down',
                color: 'teal',
                fields: this._buildProductionFields(snapshot)
            });
        },
        removeProduction(id) {
            this.productions = this.productions.filter(p => p.id !== id);
            this._save();
            const main = useMain();
            main.add_snackbar({ show: true, message: i18n.global.t("archive_deleted_successfully"), color: "success" });
        },
        removeProductions(ids) {
            const removed = this.productions.filter(p => ids.includes(p.id));
            this.productions = this.productions.filter(p => !ids.includes(p.id));
            this._save();
            const main = useMain();
            main.add_snackbar({ show: true, message: i18n.global.t("archives_deleted_successfully", { count: removed.length }), color: "success" });
        },
        async submitProduction(id) {
            const item = this.productions.find(p => p.id === id);
            if (!item) return;
            try {
                const res = await axios.post("/api/productions", item);
                this.productions = this.productions.filter(p => p.id !== id);
                this._save();
                const main = useMain();
                const notification = useNotification();
                notification.notifyCreation({ type: 'productions', icon: 'mdi-cog-outline', color: 'teal', fields: this._buildProductionFields(item) });
                main.add_snackbar({ show: true, message: i18n.global.t("archive_submitted_successfully"), color: "success" });
                return res.data;
            } catch (e) {
                console.error("Error submitting archived production:", e);
                const main = useMain();
                main.add_snackbar({ show: true, message: e.response?.data?.message || i18n.global.t("archive_submit_failed"), color: "error" });
                return null;
            }
        },
        async submitProductions(ids) {
            const items = this.productions.filter(p => ids.includes(p.id));
            let successCount = 0;
            for (const item of items) {
                try {
                    await axios.post("/api/productions", item);
                    this.productions = this.productions.filter(p => p.id !== item.id);
                    successCount++;
                } catch (e) { console.error("Error submitting archived production:", e); }
            }
            this._save();
            if (successCount > 0) {
                const main = useMain();
                const notification = useNotification();
                notification.notifyCreation({ type: 'productions', icon: 'mdi-cog-outline', color: 'teal', fields: items.flatMap(i => this._buildProductionFields(i)).slice(0, 6) });
                main.add_snackbar({ show: true, message: i18n.global.t("archives_submitted_successfully", { count: successCount }), color: "success" });
            }
        },

        // ─── Edit archive: load into form and navigate ───
        editChargeArchive(id, router) {
            const item = this.charges.find(c => c.id === id);
            if (!item) return;
            // Save to localStorage so AddCharge form can pick it up
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                localStorage.setItem(`form_charge_${userId}`, JSON.stringify(item));
                localStorage.setItem(`form_charge_showAdditionalInfo_${userId}`, 'true');
            }
            if (router) router.push({ name: 'add_charge' });
        },
        editSellArchive(id, router) {
            const item = this.sells.find(s => s.id === id);
            if (!item) return;
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                localStorage.setItem(`form_sell_${userId}`, JSON.stringify(item));
                localStorage.setItem(`form_sell_showAdditionalInfo_${userId}`, 'true');
            }
            if (router) router.push({ name: 'add_sell' });
        },
        editProductionArchive(id, router) {
            const item = this.productions.find(p => p.id === id);
            if (!item) return;
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId) {
                localStorage.setItem(`form_production_${userId}`, JSON.stringify(item));
            }
            if (router) router.push({ name: 'add_production' });
        },
    },
    getters: {
        totalCount: (state) => state.charges.length + state.sells.length + state.productions.length,
        chargeCount: (state) => state.charges.length,
        sellCount: (state) => state.sells.length,
        productionCount: (state) => state.productions.length,
    }
});
