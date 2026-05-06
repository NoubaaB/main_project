import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_inventory_draft';

export const useInventory = defineStore("inventory", {
    state: () => ({
        inventory: null,
        inventories: [],
        input_tracking_number: null,
    }),
    actions: {
        add_inventory() {
            this.inventory = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                inventory_tracking_number: [],
                description: '',
                details: [],
                total_price: 0,
                total_weight: 0,
            };
            this.input_tracking_number = null;
            this._persistDraft();
        },
        add_detail(detailCategoryId) {
            if (!this.inventory) return;
            this.inventory.details.push({
                _uid: uuidv4(),
                detail_inventory_category_id: detailCategoryId,
                article_description: '',
                stockable_type: null,
                stockable_id: null,
                weight: null,
                unit_price: null,
                total_price: null,
                package_type_id: null,
                nbr_box: null,
                warehouse_id: null,
                warehouse_operation: null,
            });
        },
        remove_detail(uid) {
            if (!this.inventory) return;
            this.inventory.details = this.inventory.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },
        recalc_totals() {
            if (!this.inventory) return;
            this.inventory.total_price = this.inventory.details.reduce((sum, d) => sum + (parseFloat(d.total_price) || 0), 0);
            this.inventory.total_weight = this.inventory.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },
        _persistDraft() {
            try {
                const data = { inventory: this.inventory, input_tracking_number: this.input_tracking_number };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },
        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.inventory) { this.inventory = data.inventory; this.input_tracking_number = data.input_tracking_number; }
                }
            } catch (e) { /* corrupted */ }
        },
        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
        },
    },
    getters: {
        getInventory: state => state.inventory,
    }
});
