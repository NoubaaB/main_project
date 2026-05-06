import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_warehouse_transfer_draft';

export const useWarehouseTransfer = defineStore("warehouseTransfer", {
    state: () => ({
        transfer: null,
        transfers: [],
        input_tracking_number: null,
    }),
    actions: {
        add_transfer() {
            this.transfer = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                transfer_tracking_number: [],
                truck_ids: [],
                description: '',
                details: [],
                total_weight: 0,
            };
            this.input_tracking_number = null;
            this._persistDraft();
        },
        add_detail(categoryId) {
            if (!this.transfer) return;
            this.transfer.details.push({
                _uid: uuidv4(),
                warehouse_transfer_category_id: categoryId,
                stockable_type: null,
                stockable_id: null,
                weight: null,
                source_warehouse_id: null,
                source_tracking_number: null,
                destination_warehouse_id: null,
                package_type_id: null,
                nbr_box: null,
                truck_id: null,
            });
        },
        remove_detail(uid) {
            if (!this.transfer) return;
            this.transfer.details = this.transfer.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },
        recalc_totals() {
            if (!this.transfer) return;
            this.transfer.total_weight = this.transfer.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },
        _persistDraft() {
            try {
                const data = { transfer: this.transfer, input_tracking_number: this.input_tracking_number };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },
        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.transfer) { this.transfer = data.transfer; this.input_tracking_number = data.input_tracking_number; }
                }
            } catch (e) { /* corrupted */ }
        },
        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
        },
    },
    getters: {
        getTransfer: state => state.transfer,
    }
});
