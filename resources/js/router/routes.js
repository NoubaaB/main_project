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
                            {
                                path: 'yield',
                                name: 'production_yield',
                                keeplive: true,
                                component: () => import('@/components/production/ProductionYield/index.vue')
                            },
                            {
                                path: 'add/:chargeId',
                                name: 'add_production',
                                keeplive: false,
                                component: () => import('@/components/production/AddProduction/index.vue')
                            },
                            {
                                path: 'view/:id',
                                name: 'view_production',
                                keeplive: false,
                                component: () => import('@/components/production/ViewProduction/index.vue')
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
                            {
                                path: "charge-category-management",
                                name: "charge_category_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/ChargeCategoryManagement/index.vue")
                            },
                            {
                                path: "wco-management",
                                name: "wco_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/WarehouseCategoryOperationManagement/index.vue")
                            },
                            {
                                path: "sell-category-management",
                                name: "sell_category_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/SellCategoryManagement/index.vue")
                            },
                            {
                                path: "production-category-management",
                                name: "production_category_management",
                                keeplive: true,
                                component: () => import("@/components/parameters/ProductionCategoryManagement/index.vue")
                            },
                        ]
                    },
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
                            {
                                path: "view_sell/:id",
                                name: "view_sell",
                                keeplive: false,
                                component: () => import("@/components/sells/ViewSell/index.vue")
                            },
                            {
                                path: "audit_trail/:id",
                                name: "sell_audit_trail",
                                keeplive: false,
                                component: () => import("@/components/sells/AuditTrail/index.vue")
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
