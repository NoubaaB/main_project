import axios from "axios";
import { defineStore } from "pinia";
import { useSupplier } from "./Supplier";
import { useShip } from "./Ship";
import { useFishType } from "./FishType";
import { useFishFamily } from "./FishFamily";
import { usePackageType } from "./PackageType";
import { useGuanoType } from "./GuanoType";
import { useWarehouse } from "./Warehouse";
import { useFishery } from "./Fishery";
import { useOrigin } from "./Origin";
import { useChargeCategory } from "./ChargeCategory";
import { useShippingCategory } from "./ShippingCategory";
import { useClient } from "./Client";
import { useFormula } from "./Formula";
import { useTruck } from "./Truck";
import { useFishProcessType } from "./FishProcessType";
import { useProductionCategory } from "./ProductionCategory";
import { useDetailChargeCategory } from "./DetailChargeCategory";
import { useDetailShippingCategory } from "./DetailShippingCategory";
import { useDetailProductionCategory } from "./DetailProductionCategory";
import { useDetailInventoryCategory } from "./DetailInventoryCategory";
import { useWarehouseTransferCategory } from "./WarehouseTransferCategory";
import { useArchive } from "./Archive";
import { useRole } from "./Role";

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
      ship: useShip(),
      fish_type: useFishType(),
      fish_family: useFishFamily(),
      package_type: usePackageType(),
      guano_type: useGuanoType(),
      warehouse: useWarehouse(),
      fishery: useFishery(),
      origin: useOrigin(),
      charge_category: useChargeCategory(),
      sell_category: useShippingCategory(),
      client: useClient(),
      formula: useFormula(),
      truck: useTruck(),
      fish_process_type: useFishProcessType(),
      production_category: useProductionCategory(),
      detail_charge_category: useDetailChargeCategory(),
      detail_sell_category: useDetailShippingCategory(),
      detail_production_category: useDetailProductionCategory(),
      detail_inventory_category: useDetailInventoryCategory(),
      warehouse_transfer_category: useWarehouseTransferCategory(),
      role_store: useRole(),
    };
  },
  actions: {
    attemp: async function () {
      axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem("token")}`;
      return axios.get("/api/me").then(async response => {
        this.user = response.data.user;
        //init local store
        this.init_local_store(response);
        // Load per-user archives
        const archive = useArchive();
        archive.loadForUser(this.user.id);
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
        // Load per-user archives
        const archive = useArchive();
        archive.loadForUser(this.user.id);
        //get data
        return response.data;
      });
    },
    init_local_store: function (response) {
      this.supplier.list = response.data.suppliers;
      this.ship.list = response.data.ships;
      this.fish_type.list = response.data.fish_types;
      this.fish_family.list = response.data.fish_families;
      this.package_type.list = response.data.package_types;
      this.guano_type.list = response.data.guano_types;
      this.warehouse.list = response.data.warehouses;
      this.charge_category.list = response.data.charge_categories;
      this.sell_category.list = response.data.shipping_categories;
      this.client.list = response.data.clients;
      this.fishery.list = response.data.fisheries;
      this.origin.list = response.data.origins;
      this.formula.list = response.data.formulas;
      this.truck.list = response.data.trucks;
      this.fish_process_type.list = response.data.fish_process_types || [];
      this.production_category.list = response.data.production_categories || [];
      this.detail_charge_category.list = response.data.detail_charge_categories || [];
      this.detail_sell_category.list = response.data.detail_shipping_categories || [];
      this.detail_production_category.list = response.data.detail_production_categories || [];
      this.detail_inventory_category.list = response.data.detail_inventory_categories || [];
      this.warehouse_transfer_category.list = response.data.warehouse_transfer_categories || [];
      this.role_store.list = response.data.roles || [];
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
    update: async function (payload) {
      const response = await axios.post('/api/update', payload);
      this.user = response.data.user;
      return response.data;
    },
  },
  getters: {
    getAuth: (state) => {
      return state.user;
    },
    is_auth: (state) => {
      return state.user != null;
    },
    isSuperAdmin: (state) => {
      return state.user?.role?.is_super_admin === true;
    },
    isAdmin: (state) => {
      if (state.user?.role?.is_super_admin) return true;
      return (state.user?.role?.permissions || []).includes('users.manage');
    },
    hasPermission: (state) => (key) => {
      if (!state.user?.role) return false;
      if (state.user.role.is_super_admin) return true;
      return (state.user.role.permissions || []).includes(key);
    },
  }
});
