import axios from "axios";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useMain } from "./Main";
import { useFishType } from "./FishType";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useWarehouse } from "./Warehouse";
import { useWarehouseCategoryOperation } from "./WarehouseCategoryOperation";
import { useProductionCategory } from "./ProductionCategory";
import { useFormula } from "./Formula";
import { useNotification } from "./Notification";
import { useAuth } from "./Auth";
import i18n from '@/plugins/i18n';

export const useProduction = defineStore("production", {
    state: () => {
        return {
            api_url: "/api/productions",
            loading: false,
            main: useMain(),
            fish_type: useFishType(),
            package_type: usePackageType(),
            guano_type: useGuanoType(),
            warehouse: useWarehouse(),
            production_category: useProductionCategory(),
            warehouse_category_operation: useWarehouseCategoryOperation(),
            formula: useFormula(),
            production_category_operation: {
                charge_details: [],
                id: null,
                total_weight: 0.00,
                total_price: 0.00,
            },
            edit_production: {},
            view_production: null,
            view_production_loading: false,
            production: {},
            productions: [],
            production_fetch_items: [],
            warehouse_stockable_tracking_number_operations: [],
            non_warehouse_stockable_operations: [],
        };
    },
    actions: {
        init_production: function (charge_id = null) {
            // Try to restore from localStorage first
            const auth = useAuth();
            const userId = auth.user?.id;
            if (userId && !charge_id) {
                try {
                    const saved = localStorage.getItem(`form_production_${userId}`);
                    if (saved) {
                        this.production = JSON.parse(saved);
                        return;
                    }
                } catch (e) {
                    console.error("Error restoring production from localStorage:", e);
                }
            }

            let production_id = uuidv4();
            let _warehouse_category_operation = this.productionWcoList[0];

            this.production = JSON.parse(JSON.stringify({
                id: production_id,
                charge_id: charge_id,
                date: new Date(),
                reception_tracking_number: [],
                tracking_onp_numbers: [],
                production_tracking_numbers: [],
                note: null,
                total_price: null,
                total_weight: null,
                origin_id: null,
                fishery_id: null,
                supplier_ids: [],
                truck_ids: [],
                production_category_id: this.production_category.list[0]?.id || null,
                warehouse_category_operations: [
                    {
                        id: _warehouse_category_operation?.id || null,
                        charge_details: [],
                        production_id: production_id,
                        total_weight: 0.00,
                        total_price: 0.00,
                    }
                ]
            }));
        },
        // ─── Form persistence ───
        saveFormToStorage: function () {
            const auth = useAuth();
            const userId = auth.user?.id;
            if (!userId) return;
            try {
                localStorage.setItem(`form_production_${userId}`, JSON.stringify(this.production));
            } catch (e) {
                console.error("Error saving production form to localStorage:", e);
            }
        },
        clearFormStorage: function () {
            const auth = useAuth();
            const userId = auth.user?.id;
            if (!userId) return;
            localStorage.removeItem(`form_production_${userId}`);
        },
        fetchProductions: async function (date_start, date_end) {
            this.loading = true;
            try {
                const res = await axios.get(this.api_url, {
                    params: { date_start, date_end }
                });
                this.production_fetch_items = res.data.productions || [];
            } catch (e) {
                console.error("Error fetching productions:", e);
            }
            this.loading = false;
        },
        fetchProductionById: async function (id) {
            this.view_production_loading = true;
            try {
                const res = await axios.get(`${this.api_url}/${id}`);
                const p = res.data.production;
                this.view_production = p;
                this.edit_production = {
                    id: p.id,
                    charge_id: p.charge_id,
                    production_category_id: p.production_category_id,
                    date: p.date,
                    note: p.note,
                    reception_tracking_number: p.reception_tracking_number || [],
                    tracking_onp_numbers: p.tracking_onp_numbers || [],
                    production_tracking_numbers: p.production_tracking_numbers || [],
                    fishery_id: p.fishery_id,
                    origin_id: p.origin_id,
                    supplier_ids: p.supplier_ids || [],
                    truck_ids: p.truck_ids || [],
                    total_weight: p.total_weight,
                    total_price: p.total_price,
                    total_package_count: p.total_package_count,
                    warehouse_category_operations: [],
                };
            } catch (e) {
                console.error("Error fetching production:", e);
            }
            this.view_production_loading = false;
        },
        submitProduction: async function () {
            this.loading = true;
            try {
                const res = await axios.post(this.api_url, this.production);
                this.main.add_snackbar({
                    show: true,
                    message: i18n.global.t("production_created_successfully"),
                    color: "success"
                });
                this.loading = false;
                return res.data;
            } catch (e) {
                console.error("Error creating production:", e);
                this.main.add_snackbar({
                    show: true,
                    message: e.response?.data?.message || i18n.global.t("error_creating_production"),
                    color: "error"
                });
                this.loading = false;
                return null;
            }
        },
        updateProduction: async function () {
            this.loading = true;
            try {
                const res = await axios.put(`${this.api_url}/${this.edit_production.id}`, this.edit_production);
                this.main.add_snackbar({
                    show: true,
                    message: i18n.global.t("production_updated_successfully"),
                    color: "success"
                });
                this.loading = false;
                return res.data;
            } catch (e) {
                console.error("Error updating production:", e);
                this.main.add_snackbar({
                    show: true,
                    message: e.response?.data?.message || i18n.global.t("error_updating_production"),
                    color: "error"
                });
                this.loading = false;
                return null;
            }
        },
        delete: async function (ids) {
            const deletedItems = this.production_fetch_items.filter(e => ids.includes(e.id));

            await axios.delete(this.api_url, { params: { ids } });
            this.production_fetch_items = this.production_fetch_items.filter(e => !ids.includes(e.id));

            const fields = [];
            const allTrackingNumbers = deletedItems.flatMap(item => item.reception_tracking_number || []);
            const allProductionTrackingNumbers = deletedItems.flatMap(item => item.production_tracking_numbers || []);

            if (allTrackingNumbers.length) {
                fields.push({
                    label: i18n.global.t('reception_tracking_number'),
                    value: allTrackingNumbers.join(', '),
                    icon: 'mdi-barcode'
                });
            }
            if (allProductionTrackingNumbers.length) {
                fields.push({
                    label: i18n.global.t('production_tracking_numbers'),
                    value: allProductionTrackingNumbers.join(', '),
                    icon: 'mdi-factory'
                });
            }

            const notification = useNotification();
            notification.notifyDeletion({
                type: 'productions',
                icon: 'mdi-cog-outline',
                color: 'error',
                fields
            });

            this.main.add_snackbar({
                show: true,
                message: i18n.global.t("productions_deleted_successfully")
                    + (allTrackingNumbers.length ? ' — T.N°: ' + allTrackingNumbers.join(', ') : '')
                    + (allProductionTrackingNumbers.length ? ' — P.T.N°: ' + allProductionTrackingNumbers.join(', ') : ''),
                color: "success"
            });
        },
        // Warehouse category operation helpers (same pattern as Charge store)
        get_first_non_disabled_warehouse_category_operation: function () {
            const wco_ids = this.production.warehouse_category_operations?.map(wco => wco.id) || [];
            return this.productionWcoList.filter(op => !wco_ids.includes(op.id))[0];
        },
        add_warehouse_category_operation: function () {
            const wco = this.get_first_non_disabled_warehouse_category_operation();
            if (!wco) return;
            this.production.warehouse_category_operations.push({
                id: wco.id,
                charge_details: [],
                production_id: this.production.id,
                total_weight: 0.00,
                total_price: 0.00,
            });
        },
        remove_warehouse_category_operation: function (index) {
            this.production.warehouse_category_operations.splice(index, 1);
        },
        checkProductionTrackingNumberUnique: async function (production_tracking_number, exclude_production_id = null) {
            try {
                const response = await axios.post('/api/productions/check-production-tracking-number', {
                    production_tracking_number,
                    exclude_production_id,
                });
                return response.data;
            } catch (error) {
                console.error('Error checking production tracking number uniqueness:', error);
                return { unique: true };
            }
        },
    },
    getters: {
        getProductionCategory: (state) => {
            return state.production_category.list.find(e => e.id == state.production.production_category_id) || {};
        },
        production_categories: (state) => {
            return state.production_category.list;
        },
        productionWcoList: (state) => {
            return state.warehouse_category_operation.getList.filter(wco => wco.operation_type === 'production');
        },
    }
});
