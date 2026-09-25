import { createRouter, createWebHistory } from "vue-router";
import store from "@/store";

import PageNotFound from "@/PageNotFound.vue";

// /***** */

import Home from "@/front/Home.vue";
// import List from "@/front/List.vue"; 
// import Order from "@/front/OrderList.vue";
// import FormOrder from "@/front/OrderListForm.vue"; 

// /***** */

import DashBoard from "@/admin/DashBoard.vue";

// /***** */

import Login from "@/admin/auth/Login.vue";
import ForgotPassword from "@/admin/auth/ForgotPassword.vue";
import PasswordReset from "@/admin/auth/PasswordReset.vue";
import Register from "@/admin/auth/Register.vue";
import CreateUser from "@/admin/auth/CreateUser.vue";

import Catalog from "@/admin/Catalog.vue";
import Product from "@/admin/Product.vue";
import Settings from "@/admin/Settings.vue";
import Category from "@/admin/Category.vue";
import SubCategory from "@/admin/SubCategory.vue";

// import Users from '@/admin/auth/Users.vue';

import Auth from "@/auth.js";

const routes = [
    // /******** Front Routes */

    { path: "/", name: "home", component: Home, meta: { layout: "front" } },
    // { path: "/dash-1", name: "list", component: List, meta: { layout: "front" } },
    // { path: "/dash-2", name: "order", component: Order, meta: { layout: "front" } },
    // { path: "/dash-3", name: "formorder", component: FormOrder, meta: { layout: "front" } },

    // /******** Admin Routes */

    { path: "/admin/login", component: Login, name: "Login", meta: { layout: "auth" }, },
    { path: "/admin/register", component: Register, name: "Register", meta: { layout: "auth" }, },
    { path: "/admin/create-user", component: CreateUser, name: "Create User", meta: { requiresAuth: true }, },
    // {path: '/admin/users' , component: Users , name: "System User" , meta: { requiresAuth: true },},

    { path: "/admin/forgot-password", component: ForgotPassword, name: "ForgotPassword", meta: { layout: "auth" }, },
    { path: "/password/reset", component: PasswordReset, name: "PasswordReset", meta: { layout: "auth" }, },

    { path: "/admin/settings", name: "Settings", component: Settings, meta: { requiresAuth: true }, },

    // /******************** */
    
    { path: "/admin", name: "Dashboard", component: DashBoard, meta: { requiresAuth: true }, },
    { path: "/admin/catalog", name: "Catalog", component: Catalog, meta: { requiresAuth: true }, },
    { path: "/admin/product", name: "Product", component: Product, meta: { requiresAuth: true }, },
    { path: "/admin/category", name: "Category", component: Category, meta: { requiresAuth: true }, },
    { path: "/admin/sub-category", name: "SubCategory", component: SubCategory, meta: { requiresAuth: true }, },

    // /******************** */

    { path: "/:catchAll(.*)*", name: "PageNotFound", component: PageNotFound, meta: { layout: "front" }, },
];

const router = new createRouter({
    history: createWebHistory(),
    linkExactActiveClass: "active",
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { left: 0, top: 0 };
        }
    },
});

router.beforeEach((to, from, next) => {
    if (to.meta && to.meta.layout && to.meta.layout == "auth") {
        store.commit("setLayout", "auth");
    } else if (to.meta && to.meta.layout && to.meta.layout == "front") {
        store.commit("setLayout", "front");
    } else {
        store.commit("setLayout", "app");
    }

    if (to.matched.some((record) => record.meta.requiresAuth)) {
        if (Auth.check()) {
            next();
            return;
        } else {
            router.push("admin/login");
        }
    } else {
        next();
    }
});

export default router;
