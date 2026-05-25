import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';

const STORAGE_KEY = 'pinia_inventory_draft';

export const useInventory = defineStore("inventory", {
    state: () => ({
        inventory: null,
        inventories: [],
        input_tracking_number: null,
        // Warehouse sections active in the "Add Inventory" wizard
        activeWarehouseSections: [],
    }),
    actions: {
        add_inventory() {
            this.inventory = {
                id: uuidv4(),
                date: new Date().toISOString().substr(0, 10),
                inventory_tracking_numbers: [],
                description: '',
                details: [],
                total_price: 0,
                total_weight: 0,
            };
            this.input_tracking_number = null;
            this.activeWarehouseSections = [];
            this._persistDraft();
        },

        /**
         * Add a warehouse section to the inventory wizard.
         * @param {number} warehouseId
         */
        add_warehouse_section(warehouseId) {
            if (!this.activeWarehouseSections.includes(warehouseId)) {
                this.activeWarehouseSections.push(warehouseId);
                this._persistDraft();
            }
        },

        /**
         * Remove a warehouse section and all its details.
         * @param {number} warehouseId
         */
        remove_warehouse_section(warehouseId) {
            this.activeWarehouseSections = this.activeWarehouseSections.filter(id => id !== warehouseId);
            if (this.inventory) {
                this.inventory.details = this.inventory.details.filter(d => d.warehouse_id !== warehouseId);
                this.recalc_totals();
            }
            this._persistDraft();
        },

        /**
         * Add a detail line to the inventory (warehouse-centric).
         * @param {Object} detail - { warehouse_id, stockable_type, stockable_id, weight, unit_price, total_price, package_type_id, nbr_box, detail_inventory_category_id, article_description, warehouse_operation }
         */
        add_detail(detail) {
            if (!this.inventory) return;
            const uid = uuidv4();
            this.inventory.details.push({
                _uid: uid,
                detail_inventory_category_id: detail.detail_inventory_category_id || null,
                article_description: detail.article_description || null,
                stockable_type: detail.stockable_type || null,
                stockable_id: detail.stockable_id || null,
                weight: detail.weight || null,
                unit_price: detail.unit_price || null,
                total_price: detail.total_price || null,
                package_type_id: detail.package_type_id || null,
                nbr_box: detail.nbr_box || null,
                warehouse_id: detail.warehouse_id || null,
                warehouse_operation: detail.warehouse_operation || 'stock_in',
            });
            this.recalc_totals();
            return uid;
        },

        /**
         * Update an existing detail by _uid.
         */
        update_detail(uid, updates) {
            if (!this.inventory) return;
            const detail = this.inventory.details.find(d => d._uid === uid);
            if (detail) {
                Object.assign(detail, updates);
                this.recalc_totals();
            }
        },

        remove_detail(uid) {
            if (!this.inventory) return;
            this.inventory.details = this.inventory.details.filter(d => d._uid !== uid);
            this.recalc_totals();
        },

        /**
         * Get details for a specific warehouse.
         */
        getWarehouseDetails(warehouseId) {
            if (!this.inventory) return [];
            return this.inventory.details.filter(d => d.warehouse_id === warehouseId);
        },

        recalc_totals() {
            if (!this.inventory) return;
            this.inventory.total_price = this.inventory.details.reduce((sum, d) => sum + (parseFloat(d.total_price) || 0), 0);
            this.inventory.total_weight = this.inventory.details.reduce((sum, d) => sum + (parseFloat(d.weight) || 0), 0);
            this._persistDraft();
        },

        _persistDraft() {
            try {
                const data = {
                    inventory: this.inventory,
                    input_tracking_number: this.input_tracking_number,
                    activeWarehouseSections: this.activeWarehouseSections,
                };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            } catch (e) { /* quota exceeded */ }
        },

        _restoreDraft() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (data.inventory) {
                        this.inventory = data.inventory;
                        this.input_tracking_number = data.input_tracking_number;
                        this.activeWarehouseSections = data.activeWarehouseSections || [];
                    }
                }
            } catch (e) { /* corrupted */ }
        },

        clearDraft() {
            sessionStorage.removeItem(STORAGE_KEY);
            this.activeWarehouseSections = [];
        },
    },
    getters: {
        getInventory: state => state.inventory,
    }
});
