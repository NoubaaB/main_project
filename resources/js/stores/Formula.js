import { defineStore } from "pinia";
import { useMain } from "./Main";
export const useFormula = defineStore("formula", {
    state: () => {
        return {
            list: [],
            model: {
                name_fr: "",
                name_en: "",
                name_es: "",
                description: "",
                enabled: true,
                type: "charge",
                expressions: [],
            },
            type: ["charge", "sell"],
            expression: "",
            chart_config: {
                chart_color: "#330099ff",
                chart_type: "column",
                scale: ""
            },
            variable: {
                "alias": "cc1_price",
                "kind": "charge",            // "charge" or "sell"
                "category_id": 1,
                "stockable_type": "App/Models/Guano",
                "stockable_id": "Guano Entier",
                "chart_color" : "#330099ff",
                "chart_type" : "column",
                "field": "total_price"      // "total_price" | "total_weight" | "total_package_count"
            },
            main:useMain()
        };
    },
    actions: {
        addFormula: function (formula) {
            this.list.push(formula);
        },
        deleteFormula: function (formula) {
            this.list = this.list.filter(e => e.id != formula.id);
        },
        updateFormula: function (formula) {
            console.log("formula", formula)
        },
        setFormulas: function (list) {
            console.log(list);
            this.list = list
        },
        getName: function (id) {
            if (id == undefined) return "N/A";
            return this.list.find(e => e.id == id)[`name_${this.main.lang.code}`]
        }
    },
    getters: {
        getList: state => state.list,
    }
});
