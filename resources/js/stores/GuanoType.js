import { defineStore } from "pinia";

export const useGuanoType = defineStore("GuanoType", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                size: "",
                description: "",
                type_emballage: null
            }
        };
    },
    actions: {
        addGuano: function (_guano) {
            this.list.push(_guano);
        },
        deleteGuano: function (_guano) {
            this.list = this.list.filter(e => e.id != _guano.id);
        },
        updateGuano: function (_guano) {
            console.log("guano_type", _guano)
        },
    },
    getters: {
        getList: state => state.list
    }
});