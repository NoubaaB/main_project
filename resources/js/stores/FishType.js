import { defineStore } from "pinia";

export const useFishType = defineStore("fish_types", {
    state: () => {
        return {
            list: [],
            model: {
                name_fr: "",
                name_en: "",
                name_es: "",
                name_in_invoice: "",
                scientific_name: "",
                image: "",
                description: "",
                process: "",
                type: null,
                fish_family_id: null,
                fish_process_type_id: null,
                productivity_color: "#1E88E5",
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
            const index = this.list.findIndex(e => e.id === fish_type.id);
            if (index !== -1) this.list.splice(index, 1, fish_type);
        },
    },
    getters: {
        getList: state => state.list
    }
});