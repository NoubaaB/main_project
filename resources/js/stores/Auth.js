import axios from "axios";
import { defineStore } from "pinia";
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
      token: null
    };
  },
  actions: {
    attemp: async function () {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem("token")}`;
      return axios.get("/api/me").then(async response => {
        this.user = response.data.user;
        //get data
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
        //get data
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