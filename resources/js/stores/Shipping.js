import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_shipping_draft';

export const useShipping = defineStore("shipping", {
    state: () => ({
        shipping: null,
        shippings: [],
        showAdditionalInfo: false,
        plafondCheckResult: null,
    }),
    actions: {
        add_shipping(categoryId) {
            this.shipping = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                shipping_category_id: categoryId || null,
                shipping_tracking_number: [],
                tracking_onp_numbers: [],
                client_id: null,
                truck_ids: [],
                origin_id: null,
                direction: null,
                fishery_id: null,
                ship_id: null,
                description: '',
                details: [],
                total_price: 0,
                total_weight: 0,
            };
            this.showAdditionalInfo = false;
            this._persistDraft();
        },
        add_detail(detailCategoryId) {
            if (!this.shipping) return;
            this.shipping.details.push({
                _uid: uuidv4(),
                detail_shipping_category_id: detailCategoryId,
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
                selected_balance_id: null,
            });
        },
        remove_detail(uid) {
            if (!this.shipping) return;
            this.shipping.details = this.shipping.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },
        recalc_totals() {
            if (!this.shipping) return;
            this.shipping.total_price = this.shipping.details.reduce((sum, d) => sum + (parseFloat(d.total_price) || 0), 0);
            this.shipping.total_weight = this.shipping.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },
        _persistDraft() {
            try {
                const data = { shipping: this.shipping, showAdditionalInfo: this.showAdditionalInfo };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },
        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.shipping) { this.shipping = data.shipping; this.showAdditionalInfo = data.showAdditionalInfo ?? false; }
                }
            } catch (e) { /* corrupted */ }
        },
        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
        },
    },
    getters: {
        getShipping: state => state.shipping,
    }
});
