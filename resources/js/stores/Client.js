import axios from "axios";
import { defineStore } from "pinia";

export const useClient = defineStore("client", {
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
            return await axios.post("/api/clients", {
                name
            }).then(res => {
                this.list.push(res.data.client);
                return res.data.client.id
            })
        },
        delete: function (client) {
            this.list = this.list.filter(e => e.id != client.id);
        },
        update: function (client) {
            console.log("client", client)
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