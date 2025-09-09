import { defineStore } from "pinia";

export const useFishery = defineStore("Fishery", {
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
        addFishery: function (_fishery) {
            this.list.push(_fishery);
        },
        deleteFishery: function (_fishery) {
            this.list = this.list.filter(e => e.id != _fishery.id);
        },
        updateFishery: function (_fishery) {
            console.log("fishery", _fishery)
        },
    },
    getters: {
        getList: state => state.list
    }
});