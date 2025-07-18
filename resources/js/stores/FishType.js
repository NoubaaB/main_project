import { defineStore } from "pinia";

export const useFishType = defineStore("fish_types", {
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
        addFishType: function (fish_type) {
            this.list.push(fish_type);
        },
        deleteFishType: function (fish_type) {
            this.list = this.list.filter(e => e.id != fish_type.id);
        },
        updateFishType: function (fish_type) {
            console.log("fish_type", fish_type)
        },
    },
    getters: {
        getList: state => state.list
    }
});