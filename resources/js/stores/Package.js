import { defineStore } from "pinia";

export const usePackage = defineStore("Package", {
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
        addPackage: function (_package) {
            this.list.push(_package);
        },
        deletePackage: function (_package) {
            this.list = this.list.filter(e => e.id != _package.id);
        },
        updatePackage: function (_package) {
            console.log("package", _package)
        },
    },
    getters: {
        getList: state => state.list
    }
});