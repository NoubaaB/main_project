import { defineStore } from "pinia";

export const useProvider = defineStore("provider", {
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
        addProvider: function (provider) {
            this.list.push(provider);
        },
        deleteProvider: function (provider) {
            this.list = this.list.filter(e => e.id != provider.id);
        },
        updateProvider: function (provider) {
            console.log("provider", provider)
        },
    },
    getters: {
        getList: state => state.list
    }
});