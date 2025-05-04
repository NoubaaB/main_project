import axios from "axios";
import { defineStore } from "pinia";
import { useDashboardWaiter } from "@/stores/DashboardWaiter";
import { useClient } from "./Client";
import { useItem } from "./Item";
import { useCommand } from "./Command";
export const useAuth = defineStore("Auth", {
  state: () => {
    return {
      model: {
        full_name: null,
        phone: null,
        address: null,
        image: null,
        email: null,
        password: null,
        confirmPassword: null
      },
      user: null,
      dashboard_waiter: useDashboardWaiter(),
      client: useClient(),
      item: useItem(),
      command: useCommand(),
      token: null
    };
  },
  actions: {
    attemp: async function () {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem("token")}`;
      return axios.get("/api/me").then(async response => {
        this.user = response.data.user;
        this.setData(response.data.clients, response.data.items , response.data.bills);
        return await this.dashboard_waiter.getData().then(res => res);
      })
    },
    login: async function () {
      return axios.post("/api/login", {
        email: this.model.email,
        password: this.model.password,
      }).then(async response => {
        this.user = response.data.user;
        this.token = response.data.token;
        localStorage.setItem('token', this.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        this.setData(response.data.clients, response.data.items , response.data.bills);
        await this.dashboard_waiter.getData();
        return response.data;
      });
    },
    set_user: function (user) {
      this.user = user;
    },
    logout: async function () {
      console.log("logout");
      return axios.post("/api/logout",).then(response => {
        this.user = null;
        this.token = null;
        this.setData([], [] , []);
        localStorage.setItem('token', null);
        return response.data;
      });
    },
    forget_password: async function () {
      console.log("this.state", this.model.email)
    },
    reset_password: async function (token) {
      console.log("this.state", this.model.email, this.model.password, this.model.confirmPassword, token)
    },
    update: async function () {

    },
    setData: function (clients, items , bills) {
      this.client.list = clients;
      this.item.list = items;
      this.command.bills = bills;
    }
  },
  getters: {
    getAuth: (state) => {
      return state.user;
    },
    is_auth: (state) => {
      return state.user != null;
    },
  }
});