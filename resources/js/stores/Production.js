import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_production_draft';

export const useProduction = defineStore("production", {
    state: () => ({
        production: null,
        productions: [],
        plafondCheckResult: null,
    }),
    actions: {
        add_production(categoryId) {
            this.production = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                production_category_id: categoryId || null,
                reception_tracking_number: [],
                production_tracking_number: [],
                tracking_onp_numbers: [],
                production_type: null,
                charge_id: null,
                supplier_id: null,
                client_id: null,
                fishery_id: null,
                ship_id: null,
                origin_id: null,
                truck_ids: [],
                description: '',
                details: [],
                total_price: 0,
                total_weight: 0,
            };
            this._persistDraft();
        },
        add_detail(detailCategoryId) {
            if (!this.production) return;
            this.production.details.push({
                _uid: uuidv4(),
                detail_production_category_id: detailCategoryId,
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
            if (!this.production) return;
            this.production.details = this.production.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },
        recalc_totals() {
            if (!this.production) return;
            this.production.total_price = this.production.details.reduce((sum, d) => sum + (parseFloat(d.total_price) || 0), 0);
            this.production.total_weight = this.production.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },
        _persistDraft() {
            try {
                const data = { production: this.production };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },
        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.production) { this.production = data.production; }
                }
            } catch (e) { /* corrupted */ }
        },
        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
        },
    },
    getters: {
        getProduction: state => state.production,
    }
});
