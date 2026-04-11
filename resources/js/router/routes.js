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
                        component: () => import("@/components/ai_prompt/index.vue") //create origin component later
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
                            {
                                path: "audit_trail/:id",
                                name: "charge_audit_trail",
                                keeplive: false,
                                component: () => import("@/components/charges/AuditTrail/index.vue")
                            },
                        ]
                    },
                    {
                        path: '/production',
                        name: 'production',
                        children: [
                            {
                                path: 'kanban',
                                name: 'production_kanban',
                                keeplive: true,
                                component: () => import('@/components/production/KanbanProduction/index.vue')
                            },
                            {
                                path: 'list',
                                name: 'production_list',
                                keeplive: true,
                                component: () => import('@/components/production/ListProduction/index.vue')
                            },
                        ]
                    },
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
                        ]
                    },
                    {
                        path: "/warehouses",
                        name: "warehouses",
                        keeplive: true,
                        component: () => import("@/components/warehouses/index.vue")
                    },
                    {
                        path: "/inventory",
                        name: "inventory",
                        keeplive: true,
                        component: () => import("@/components/warehouses/Inventory.vue")
                    },
                    {
                        path: "/inventory-history",
                        name: "inventory_history",
                        keeplive: true,
                        component: () => import("@/components/warehouses/InventoryHistory.vue")
                    },
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
                        ]
                    },
                    {
                        path: "/sells",
                        keeplive: true,
                        name: "sells",
                        children: [
                            {
                                path: "add_sell",
                                keeplive: true,
                                name: "add_sell",
                                component: () => import("@/components/sells/AddSell/index.vue")
                            },
                            {
                                path: "list_sell",
                                name: "list_sell",
                                keeplive: true,
                                component: () => import("@/components/sells/ListSell/index.vue")
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
