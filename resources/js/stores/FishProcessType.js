import { defineStore } from "pinia";

export const useFishProcessType = defineStore("fish_process_types", {
    state: () => {
        return {
            list: [],
        };
    },
    actions: {
        addFishProcessType: function (item) {
            this.list.push(item);
        },
        deleteFishProcessType: function (item) {
            this.list = this.list.filter(e => e.id != item.id);
        },
    },
    getters: {
        getList: state => state.list
    }
});
