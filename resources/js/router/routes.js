import { useAuth } from "@/stores/Auth";

const routes = [
    {
        path: "/error",
        name: "error",
        component: () => import("@/layouts/default.vue"),
        redirect: { name: "error404" },
        children: [
            {
                path: "404",
                name: "error404",
                component: () => import("@/components/errors/Error404.vue")
            }
        ],
    },

    {
        path: "/:pathMatch(.*)*",
        name: "NotFound",
        beforeEnter: function (to, from, next) {
            next({ name: "error404" })
        }
    },
    {
        path: "/",
        meta: { keeplive: true },
        component: () => import("@/layouts/default.vue"),
        children: [
            {
                path: "/",
                beforeEnter: function (to, from, next) {
                    if (useAuth().is_auth) next();
                    else next({ name: "login" });
                },
                children: [
                    {
                        path: "/",
                        name: "index",
                        keeplive: true,
                        component: () => import("@/components/dashboard/index.vue")
                    },
                    {
                        path: "/profile",
                        name: "profile_settings",
                        keeplive: false,
                        component: () => import("@/components/auth/ProfileSettings/index.vue")
                    },
                    {
                        path: "/admin/users",
                        name: "admin_users",
                        keeplive: false,
                        component: () => import("@/components/admin/UserRoleManagement/index.vue"),
                        beforeEnter: (to, from, next) => {
                            const auth = useAuth();
                            if (auth.isAdmin) next();
                            else next({ name: 'index' });
                        }
                    },
                    {
                        path: "/ai_prompt",
                        name: "ai_prompt",
                        keeplive: true,
                        component: () => import("@/components/ai_prompt/index.vue")
                    },
                    {
                        path: "/command",
                        name: "command",
                        keeplive: true,
                        redirect: { name: "command.list" },
                    },
                    // =======================================
                    //  CHARGES
                    // =======================================
                    {
                        path: "/charges",
                        keeplive: true,
                        name: "charges",
                        children: [
                            {
                                path: "add_charge",
                                keeplive: true,
                                name: "add_charge",
                                component: () => import("@/components/charges/AddCharge/index.vue")
                            },
                            {
                                path: "edit_charge/:id",
                                keeplive: false,
                                name: "edit_charge",
                                component: () => import("@/components/charges/EditCharge/index.vue")
                            },
                            {
                                path: "list_charge",
                                name: "list_charge",
                                keeplive: true,
                                component: () => import("@/components/charges/ListCharge/index.vue")
                            },
                            {
                                path: "view_charge/:id",
                                name: "view_charge",
                                keeplive: false,
                                component: () => import("@/components/charges/ViewCharge/index.vue")
                            },
                        ]
                    },
                    // =======================================
                    //  PRODUCTIONS
                    // =======================================
                    {
                        path: '/production',
                        name: 'production',
                        children: [
                            {
                                path: 'list',
                                name: 'production_list',
                                keeplive: true,
                                component: () => import('@/components/production/ListProduction/index.vue')
                            },
                            {
                                path: 'add',
                                name: 'add_production',
                                keeplive: false,
                                component: () => import('@/components/production/AddProduction/index.vue')
                            },
                            {
                                path: 'edit/:id',
                                name: 'edit_production',
                                keeplive: false,
                                component: () => import('@/components/production/EditProduction/index.vue')
                            },
                            {
                                path: 'view/:id',
                                name: 'view_production',
                                keeplive: false,
                                component: () => import('@/components/production/ViewProduction/index.vue')
                            },
                            {
                                path: 'charge-management',
                                name: 'charge_production_management',
                                component: () => import('@/components/production/ChargeProductionManagement/index.vue')
                            },
                        ]
                    },
                    // =======================================
                    //  SHIPPINGS
                    // =======================================
                    {
                        path: "/shippings",
                        keeplive: true,
                        name: "shippings",
                        children: [
                            {
                                path: "add_shipping",
                                keeplive: true,
                                name: "add_shipping",
                                component: () => import("@/components/shippings/AddShipping/index.vue")
                            },
                            {
                                path: "edit_shipping/:id",
                                name: "edit_shipping",
                                keeplive: false,
                                component: () => import("@/components/shippings/EditShipping/index.vue")
                            },
                            {
                                path: "list_shipping",
                                name: "list_shipping",
                                keeplive: true,
                                component: () => import("@/components/shippings/ListShipping/index.vue")
                            },
                            {
                                path: "view_shipping/:id",
                                name: "view_shipping",
                                keeplive: false,
                                component: () => import("@/components/shippings/ViewShipping/index.vue")
                            },
                        ]
                    },
                    // =======================================
                    //  INVENTORY
                    // =======================================
                    {
                        path: '/inventory',
                        name: 'inventory',
                        children: [
                            {
                                path: 'add',
                                name: 'add_inventory',
                                keeplive: true,
                                component: () => import('@/components/inventory/AddInventory/index.vue')
                            },
                        ]
                    },
                    // =======================================
                    //  WAREHOUSE TRANSFERS
                    // =======================================
                    {
                        path: '/warehouse-transfers',
                        name: 'warehouse_transfers',
                        children: [
                            {
                                path: 'list',
                                name: 'list_warehouse_transfer',
                                keeplive: true,
                                component: () => import('@/components/warehouse_transfers/AddWarehouseTransfer/index.vue')
                            },
                            {
                                path: 'edit/:id',
                                name: 'edit_warehouse_transfer',
                                component: () => import('@/components/warehouse_transfers/EditWarehouseTransfer/index.vue')
                            },
                        ]
                    },
                    // =======================================
                    //  SUPPLIER INVOICE
                    // =======================================
                    {
                        path: "/supplier_invoice",
                        keeplive: true,
                        name: "supplier_invoice",
                        children: [
                            {
                                path: "view_supplier_invoice",
                                name: "view_supplier_invoice",
                                component: () => import("@/components/SupplierInvoice/ViewSupplierInvoice/index.vue")
                            },
                            {
                                path: "list_supplier_invoice",
                                name: "list_supplier_invoice",
                                component: () => import("@/components/SupplierInvoice/ListSupplierInvoice/index.vue")
                            },
                            {
                                path: "supplier_settlement",
                                name: "supplier_settlement",
                                component: () => import("@/components/SupplierPayment/SupplierSettlement/index.vue")
                            },
                        ]
                    },
                    // =======================================
                    //  CLIENT INVOICE
                    // =======================================
                    {
                        path: "/client_invoice",
                        keeplive: true,
                        name: "client_invoice",
                        children: [
                            {
                                path: "view_client_invoice",
                                name: "view_client_invoice",
                                component: () => import("@/components/ClientInvoice/ViewClientInvoice/index.vue")
                            },
                            {
                                path: "list_client_invoice",
                                name: "list_client_invoice",
                                component: () => import("@/components/ClientInvoice/ListClientInvoice/index.vue")
                            },
                            {
                                path: "client_settlement",
                                name: "client_settlement",
                                component: () => import("@/components/ClientPayment/ClientSettlement/index.vue")
                            },
                        ]
                    },
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    //  WAREHOUSES
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    {
                        path: "/warehouses",
                        name: "warehouses",
                        keeplive: true,
                        component: () => import("@/components/warehouses/index.vue")
                    },
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    //  PARAMETERS
                    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
                    {
                        path: "/parameters",
                        name: "parameters",
                        children: [
                            {
                                path: "warehouse-management",
                                name: "warehouse_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/WarehouseManagement/index.vue")
                            },
                            {
                                path: "fish-management",
                                name: "fish_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/FishManagement/index.vue")
                            },
                            {
                                path: "charge-management",
                                name: "charge_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/ChargeManagement/index.vue")
                            },
                            {
                                path: "shipping-management",
                                name: "shipping_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/ShippingManagement/index.vue")
                            },
                            {
                                path: "production-management",
                                name: "production_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/ProductionManagement/index.vue")
                            },
                            {
                                path: "detail-inventory-category-management",
                                name: "detail_inventory_category_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/DetailInventoryCategoryManagement/index.vue")
                            },
                            {
                                path: "warehouse-transfer-category-management",
                                name: "warehouse_transfer_category_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/WarehouseTransferCategoryManagement/index.vue")
                            },
                            {
                                path: "guano-management",
                                name: "guano_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/GuanoManagement/index.vue")
                            },
                            {
                                path: "package-management",
                                name: "package_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/PackageManagement/index.vue")
                            },
                            {
                                path: "bank-name-management",
                                name: "bank_name_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/BankNameManagement/index.vue")
                            },
                        ]
                    },
                    // =======================================
                    //  WORKFORCE
                    // =======================================
                    {
                        path: '/workforce',
                        name: 'workforce',
                        children: [
                            {
                                path: 'management',
                                name: 'workforce_management',
                                keeplive: true,
                                component: () => import('@/components/workforce/WorkforceManagement/index.vue')
                            },
                            {
                                path: 'corporals',
                                name: 'corporal_management',
                                keeplive: true,
                                component: () => import('@/components/workforce/CorporalManagement/index.vue')
                            },
                            {
                                path: 'sections',
                                name: 'section_management',
                                keeplive: true,
                                component: () => import('@/components/workforce/SectionManagement/index.vue')
                            },
                            {
                                path: 'fortnight',
                                name: 'fortnight_management',
                                keeplive: true,
                                component: () => import('@/components/workforce/FortnightManagement/index.vue')
                            },
                            {
                                path: 'statistics',
                                name: 'workforce_statistics',
                                keeplive: true,
                                component: () => import('@/components/workforce/WorkforceStatistics/index.vue')
                            },
                        ]
                    },
                ]
            },
            {
                path: "auth",
                name: "auth",
                redirect: { name: 'login' },
                beforeEnter: function (to, from, next) {
                    if (useAuth().is_auth) next({ name: 'index' });
                    next();
                },
                children: [
                    {
                        path: "login",
                        name: "login",
                        component: () => import("@/components/auth/login/Layout.vue")
                    }
                ]
            }
        ]
    }
]

export default routes;

