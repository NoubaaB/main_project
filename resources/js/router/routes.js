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
                                path: "list_charge",
                                name: "list_charge",
                                keeplive:true,
                                component: () => import("@/components/charges/ListCharge/index.vue")
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
                                keeplive: true,
                                name: "view_supplier_invoice",
                                component: () => import("@/components/SupplierInvoice/ViewSupplierInvoice/index.vue")
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