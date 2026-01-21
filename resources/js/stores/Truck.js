import axios from "axios";
import { defineStore } from "pinia";

export const useTruck = defineStore("truck", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                total_paid: 0.00,
                total_non_paid: ""
            }
        };
    },
    actions: {
        add: async function (name) {
            return await axios.post("/api/trucks", {
                name
            }).then(res => {
                this.list.push(res.data.truck);
                return res.data.truck.id
            })
        },
        delete: function (truck) {
            this.list = this.list.filter(e => e.id != truck.id);
        },
        update: function (truck) {
            console.log("truck", truck)
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