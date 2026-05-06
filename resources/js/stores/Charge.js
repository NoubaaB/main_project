import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_charge_draft';

export const useCharge = defineStore("charge", {
    state: () => ({
        charge: null,
        charges: [],
        input_tracking_number: null,
        showAdditionalInfo: false,
    }),
    actions: {
        add_charge(categoryId) {
            this.charge = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                charge_category_id: categoryId || null,
                reception_tracking_number: [],
                tracking_onp_numbers: [],
                supplier_id: null,
                truck_ids: [],
                fishery_id: null,
                ship_id: null,
                origin_id: null,
                description: '',
                details: [],
                total_price: 0,
                total_weight: 0,
            };
            this.input_tracking_number = null;
            this.showAdditionalInfo = false;
            this._persistDraft();
        },
        add_detail(detailCategoryId) {
            if (!this.charge) return;
            this.charge.details.push({
                _uid: uuidv4(),
                detail_charge_category_id: detailCategoryId,
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
                truck_id: null,
            });
        },
        remove_detail(uid) {
            if (!this.charge) return;
            this.charge.details = this.charge.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },
        recalc_totals() {
            if (!this.charge) return;
            this.charge.total_price = this.charge.details.reduce((sum, d) => sum + (parseFloat(d.total_price) || 0), 0);
            this.charge.total_weight = this.charge.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },
        _persistDraft() {
            try {
                const data = { charge: this.charge, input_tracking_number: this.input_tracking_number, showAdditionalInfo: this.showAdditionalInfo };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },
        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.charge) { this.charge = data.charge; this.input_tracking_number = data.input_tracking_number; this.showAdditionalInfo = data.showAdditionalInfo ?? false; }
                }
            } catch (e) { /* corrupted */ }
        },
        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
        },
    },
    getters: {
        getCharge: state => state.charge,
    }
});
