import axios from "axios";
import { defineStore } from "pinia";

export const useSupplierInvoice = defineStore("supplierInvoice", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                total_paid: 0.00,
                total_non_paid: ""
            },
            final_page_rows_nbr: 35,
            active_page_rows_nbr: 32
        };
    },
    actions: {
        add: async function (name) {
            return await axios.post("/api/supplierInvoices", {
                name
            }).then(res => {
                this.list.push(res.data.supplierInvoice);
                return res.data.supplierInvoice.id
            })
        },
        delete: function (supplierInvoice) {
            this.list = this.list.filter(e => e.id != supplierInvoice.id);
        },
        update: function (supplierInvoice) {
            console.log("supplierInvoice", supplierInvoice)
        },
        getName: function (id) {
            return this.list.find(e => e.id == id).name;
        }
    },
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e => { return { id: e.id, name: e.name } })
    }
});