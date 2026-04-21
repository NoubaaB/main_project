import { defineStore } from "pinia";

export const useFishFamily = defineStore("fish_families", {
    state: () => {
        return {
            list: [],
            model: {
                name_fr: "",
                name_en: "",
                name_es: "",
            }
        };
    },
    actions: {
        addFishFamily: function (fish_family) {
            this.list.push(fish_family);
        },
        deleteFishFamily: function (fish_family) {
            this.list = this.list.filter(e => e.id != fish_family.id);
        },
        updateFishFamily: function (fish_family) {
            const index = this.list.findIndex(e => e.id === fish_family.id);
            if (index !== -1) this.list.splice(index, 1, fish_family);
        },
    },
    getters: {
        getList: state => state.list
    }
});