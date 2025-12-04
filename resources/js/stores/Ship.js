import axios from "axios";
import { defineStore } from "pinia";

export const useShip = defineStore("ship", {
    state: () => {
        return {
            list: [],
            model: {
                name: ""
            }
        };
    },
    actions: {
        add: async function (name) {
            return await axios.post("/api/ships", {
                name
            }).then(res => {
                this.list.push(res.data.ship);
                return res.data.ship.id
            })
        },
        delete: function (ship) {
            this.list = this.list.filter(e => e.id != ship.id);
        },
        update: function (ship) {
            console.log("ship", ship)
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