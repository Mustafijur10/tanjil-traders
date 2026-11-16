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

import Category from "@/admin/Category.vue";
import SalesReport from "@/admin/SalesReport.vue";
import SubCategory from "@/admin/SubCategory.vue";
import Order from "@/admin/Order.vue";
import Pending from "@/admin/Pending.vue";
import Processing from "@/admin/Processing.vue";
import CustomerReport from "@/admin/CustomerReport.vue";
import InventoryReport from "@/admin/InventoryReport.vue";
import Brand from "@/admin/Brand.vue";
import CreateOrder from "@/admin/CreateOrder.vue";

// marketting 

import Discount from "@/admin/marketting/Discount.vue";

// settings: starts

import Settings from "@/admin/settings/Settings.vue";
import GeneralSettings from "@/admin/settings/GeneralSettings.vue";
import StoreFront from "@/admin/settings/StoreFront.vue";
import PaymentMethods from "@/admin/settings/PaymentMethods.vue";
import StaffRoles from "@/admin/settings/StaffRoles.vue";
import ShippingZones from "@/admin/settings/ShippingZones.vue";

// settings: end 

import SalesAnalytics from "@/admin/SalesAnalytics.vue";


import ProfileOverview from "@/admin/ProfileOverview.vue";
import ProfileEdit from "@/admin/ProfileEdit.vue";
import SeoAnalytics from "@/admin/SeoAnalytics.vue";

// message center

import Inbox from "@/admin/message-center/Inbox.vue";
import CreatePost from "@/admin/message-center/CreatePost.vue";
import ContentCalendar from "@/admin/message-center/ContentCalendar.vue";
import Campaigns from "@/admin/message-center/Campaigns.vue";
import Audiences from "@/admin/message-center/Audiences.vue";
import MessageTemplates from "@/admin/message-center/MessageTemplates.vue";
// import Automations from "@/admin/message-center/Automations.vue";
// import Channels from "@/admin/message-center/Channels.vue";
// import ChannelReports from "@/admin/message-center/ChannelReports.vue";


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
    { path: "/admin/sales-report", name: "SalesReport", component: SalesReport, meta: { requiresAuth: true }, },
    { path: "/admin/sub-category", name: "SubCategory", component: SubCategory, meta: { requiresAuth: true }, },
    { path: "/admin/orders", name: "Order", component: Order, meta: { requiresAuth: true }, },
    { path: "/admin/pending", name: "Pending", component: Pending, meta: { requiresAuth: true }, },
    { path: "/admin/orders/processing", name: "Processing", component: Processing, meta: { requiresAuth: true }, },
    { path: "/admin/customer-report", name: "CustomerReport", component: CustomerReport, meta: { requiresAuth: true }, },
    { path: "/admin/inventory-report", name: "InventoryReport", component: InventoryReport, meta: { requiresAuth: true }, },
    { path: "/admin/brand", name: "Brand", component: Brand, meta: { requiresAuth: true }, },
    { path: "/admin/create-order", name: "CreateOrder", component: CreateOrder, meta: { requiresAuth: true }, },
    { path: "/admin/sales-analytics", name: "SalesAnalytics", component: SalesAnalytics, meta: { requiresAuth: true }, },


    // message center starts
    { path: "/admin/inbox", name: "Inbox", component: Inbox, meta: { requiresAuth: true } },
    // { path: "/admin/inbox/:conversationId", name: "InboxThread", component: Inbox, props: true, meta: { requiresAuth: true } },
    { path: "/admin/publish", name: "CreatePost", component: CreatePost, meta: { requiresAuth: true } },
    { path: "/admin/content-calendar", name: "ContentCalendar", component: ContentCalendar, meta: { requiresAuth: true } },
    { path: "/admin/campaigns", name: "Campaigns", component: Campaigns, meta: { requiresAuth: true } },
    { path: "/admin/audiences", name: "Audiences", component: Audiences, meta: { requiresAuth: true } },
    { path: "/admin/message-templates", name: "MessageTemplates", component: MessageTemplates, meta: { requiresAuth: true } },
    // { path: "/admin/automations", name: "Automations", component: Automations, meta: { requiresAuth: true } },
    // { path: "/admin/channels", name: "Channels", component: Channels, meta: { requiresAuth: true } },
    // { path: "/admin/channel-reports", name: "ChannelReports", component: ChannelReports, meta: { requiresAuth: true } },
    // message center ends

    { path: "/admin/storefront", name: "StoreFront", component: StoreFront, meta: { requiresAuth: true }, },
    { path: "/admin/general-settings", name: "GeneralSettings", component: GeneralSettings, meta: { requiresAuth: true } },
    { path: "/admin/settings-payment", name: "PaymentMethods", component: PaymentMethods, meta: { requiresAuth: true } },
    { path: "/admin/roles", name: "StaffRoles", component: StaffRoles, meta: { requiresAuth: true } },
    { path: "/admin/shipping", name: "ShippingZones", component: ShippingZones, meta: { requiresAuth: true } },

    { path: "/admin/profile", name: "ProfileOverview", component: ProfileOverview, meta: { requiresAuth: true } },
    { path: "/admin/profile-edit", name: "ProfileEdit", component: ProfileEdit, meta: { requiresAuth: true } },
    { path: "/admin/seo-analytics", name: "SeoAnalytics", component: SeoAnalytics, meta: { requiresAuth: true } },
    
    // marketting 
    { path: "/admin/discount", name: "Discount", component: Discount, meta: { requiresAuth: true } },

    //
    // admin children
    { path: "/admin/pages", component: () => import("@/admin/Pages.vue") },
    { path: "/admin/pages/new", component: () => import("@/admin/Pages.vue") },
    { path: "/admin/pages/:id(\\d+)/edit", component: () => import("@/admin/Pages.vue") },

    // public site: ONE route for every CMS page. Add it LAST, after every other storefront route.
    // (Vue Router can't use nested groups like (?:...) in a param pattern, so the pattern stays simple.)
    { path: "/:slug([a-z0-9-]+)", component: () => import("@/admin/pages/CmsPage.vue") },
    

    // /******************** */

    { path: "/:catchAll(.*)*", name: "PageNotFound", component: PageNotFound, meta: { layout: "front", hideChrome: true }, },
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
