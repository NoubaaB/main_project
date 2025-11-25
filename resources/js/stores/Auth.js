import axios from "axios";
import { defineStore } from "pinia";
import { useSupplier } from "./Supplier";
import { useFishType } from "./FishType";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useWarehouse } from "./Warehouse";
import { useWarehouseCategoryOperation } from "./WarehouseCategoryOperation";
import { useFishery } from "./Fishery";
import { useOrigin } from "./Origin";
import { useChargeCategory } from "./ChargeCategory";
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
      token: null,
      supplier: useSupplier(),
      fish_type: useFishType(),
      package_type: usePackageType(),
      guano_type: useGuanoType(),
      warehouse: useWarehouse(),
      warehouse_category_operation: useWarehouseCategoryOperation(),
      fishery: useFishery(),
      origin: useOrigin(),
      charge_category: useChargeCategory(),
    };
  },
  actions: {
    attemp: async function () {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem("token")}`;
      return axios.get("/api/me").then(async response => {
        this.user = response.data.user;
        //init local store
        this.init_local_store(response);
      })
    },
    login: async function () {
      return axios.post("/api/login", {
        email: this.model.email,
        password: this.model.password,
      }).then(async response => {
        this.user = response.data.user;
        //init local store
        this.init_local_store(response);
        this.token = response.data.token;
        localStorage.setItem('token', this.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        //get data
        return response.data;
      });
    },
    init_local_store: function (response) {
      this.supplier.list = response.data.suppliers;
      this.fish_type.list = response.data.fish_types;
      this.package_type.list = response.data.package_types;
      this.guano_type.list = response.data.guano_types;
      this.warehouse.list = response.data.warehouses;
      this.warehouse_category_operation.list = response.data.warehouse_category_operations;
      this.charge_category.list = response.data.charge_categories;
      this.fishery.list = response.data.fisheries;
      this.origin.list = response.data.origins;
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