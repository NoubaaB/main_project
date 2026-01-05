import { defineStore } from "pinia";

export const useFishFamily = defineStore("fish_families", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                scientific_name: "",
                image: "",
                description: "",
                fish_familly_id:null
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
            console.log("fish_family", fish_family)
        },
    },
    getters: {
        getList: state => state.list
    }
});