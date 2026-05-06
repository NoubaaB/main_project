import { defineStore } from "pinia";

export const useRole = defineStore("Role", {
  state: () => ({
    list: [],
  }),
  getters: {
    getRoleById: (state) => (id) => state.list.find(r => r.id === id),
  },
});
