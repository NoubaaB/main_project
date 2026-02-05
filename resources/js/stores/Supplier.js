import { defineStore } from "pinia";

export const useSupplier = defineStore("supplier", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                ice: "",
                country: "",
                city: "",
                address: "",
                tel: "",
                logo: null,
                state: true, // Default to true
            }
        };
    },
    actions: {
        addSupplier: function (supplier) {
            this.list.push(supplier);
        },
        deleteSupplier: function (supplier) {
            this.list = this.list.filter(e => e.id != supplier.id);
        },
        updateSupplier: function (supplier) {
            console.log("supplier", supplier)
        },
        get_supplier_name_by_id: function (id) {
            let name = this.list.find(s => s.id == id)?.name;
            return name;
        }
    },
    getters: {
        getList: state => state.list
    }
});