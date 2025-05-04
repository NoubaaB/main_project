import { defineStore } from "pinia";

export const useItem = defineStore("item", {
    state: () => {
        return {
            list: [],
            model: {
                name: "",
                price: 0.00,
                image: "",
                description: ""
            }
        };
    },
    actions: {
        addItem: function (item) {
            this.list.push(item);
        },
        deleteItem: function (item) {
            this.list = this.list.filter(e => e.id != item.id);
        },
        updateItem: function (item) {
            console.log("item", item)
        },
        setItems: function (list) {
            console.log(list);
            this.list = list
        },
        getName: function (id) {
            return this.list.find(e => e.id == id).name;
        }
    },
    getters: {
        getList: state => state.list,
        getMinList: state => state.list.map(e=>{return {id:e.id , name : e.name , image:e.image}})
    }
});