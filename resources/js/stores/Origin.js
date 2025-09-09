import { defineStore } from "pinia";

export const useOrigin = defineStore("Origin", {
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
        addOrigin: function (_origin) {
            this.list.push(_origin);
        },
        deleteOrigin: function (_origin) {
            this.list = this.list.filter(e => e.id != _origin.id);
        },
        updateOrigin: function (_origin) {
            console.log("origin", _origin)
        },
    },
    getters: {
        getList: state => state.list
    }
});