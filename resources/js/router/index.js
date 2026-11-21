/*
 * Routes. meta:
 *   layout  - "front" (storefront), "admin" (admin panel) or "auth" (sign-in pages)
 *   staff   - only admin and staff accounts
 *   admin   - only admin accounts
 *   guest   - only when signed out (sign-in pages)
 *   title   - English page title (translated automatically)
 * Pages are loaded on demand so each page only downloads its own code.
 */
import { createRouter, createWebHistory } from "vue-router";
import store from "@/store";
import { t } from "@/plugins/i18n";

const admin = (path, component, title, extra = {}) => ({
    path: `/admin${path}`,
    component,
    meta: { layout: "admin", staff: true, title, ...extra },
});

const settingsPage = () => import("@/admin/settings/SettingsPage.vue");

const routes = [
    // ── Storefront ──
    { path: "/", name: "home", component: () => import("@/front/Home.vue"), meta: { layout: "front", title: "Home" } },
    { path: "/reset-password", component: () => import("@/admin/auth/PasswordReset.vue"), meta: { layout: "auth", title: "Reset password" } },
    { path: "/contact", component: () => import("@/front/Contact.vue"), meta: { layout: "front", title: "Contact us" } },

    // Shop: one page component for every product list
    { path: "/shop", component: () => import("@/front/Shop.vue"), meta: { layout: "front", title: "All products" } },
    { path: "/category/:slug", component: () => import("@/front/Shop.vue"), meta: { layout: "front" } },
    { path: "/brand/:slug", component: () => import("@/front/Shop.vue"), meta: { layout: "front" } },
    { path: "/search", component: () => import("@/front/Shop.vue"), meta: { layout: "front", title: "Search" } },
    { path: "/deals", component: () => import("@/front/Shop.vue"), meta: { layout: "front", title: "Deals and offers" } },
    { path: "/brands", component: () => import("@/front/Brands.vue"), meta: { layout: "front", title: "Brands" } },
    { path: "/product/:slug", component: () => import("@/front/ProductPage.vue"), meta: { layout: "front" } },
    { path: "/compare", component: () => import("@/front/Compare.vue"), meta: { layout: "front", title: "Compare products" } },

    // Cart, checkout and orders
    { path: "/cart", component: () => import("@/front/Cart.vue"), meta: { layout: "front", title: "Cart" } },
    { path: "/checkout", component: () => import("@/front/Checkout.vue"), meta: { layout: "front", title: "Checkout" } },
    { path: "/order-placed/:number", component: () => import("@/front/OrderPlaced.vue"), meta: { layout: "front", title: "Order placed" } },
    { path: "/track-order", component: () => import("@/front/TrackOrder.vue"), meta: { layout: "front", title: "Track your order" } },
    { path: "/wishlist", component: () => import("@/front/Wishlist.vue"), meta: { layout: "front", title: "Wishlist" } },

    // Customer accounts
    { path: "/login", component: () => import("@/front/CustomerAuth.vue"), meta: { layout: "front", guest: true, title: "Sign in" } },
    { path: "/register", component: () => import("@/front/CustomerAuth.vue"), meta: { layout: "front", guest: true, title: "Create account" } },
    { path: "/forgot-password", component: () => import("@/admin/auth/ForgotPassword.vue"), meta: { layout: "auth", guest: true, title: "Forgot password" } },
    { path: "/account", component: () => import("@/front/Account.vue"), meta: { layout: "front", customer: true, tab: "overview", title: "My account" } },
    { path: "/account/orders", component: () => import("@/front/Account.vue"), meta: { layout: "front", customer: true, tab: "orders", title: "My orders" } },
    { path: "/account/profile", component: () => import("@/front/Account.vue"), meta: { layout: "front", customer: true, tab: "profile", title: "Profile" } },
    { path: "/account/orders/:number", component: () => import("@/front/AccountOrder.vue"), meta: { layout: "front", customer: true, title: "Order" } },

    // ── Admin sign-in ──
    { path: "/admin/login", component: () => import("@/admin/auth/Login.vue"), meta: { layout: "auth", guest: true, title: "Sign in" } },
    { path: "/admin/forgot-password", component: () => import("@/admin/auth/ForgotPassword.vue"), meta: { layout: "auth", guest: true, title: "Forgot password" } },

    // ── Admin ──
    admin("", () => import("@/admin/DashBoard.vue"), "Dashboard"),

    // Catalog
    admin("/products", () => import("@/admin/catalog/Products.vue"), "Products"),
    admin("/products/new", () => import("@/admin/catalog/ProductForm.vue"), "Add product"),
    admin("/products/:id(\\d+)", () => import("@/admin/catalog/ProductForm.vue"), "Edit product"),
    admin("/categories", () => import("@/admin/catalog/Categories.vue"), "Categories"),
    admin("/brands", () => import("@/admin/catalog/Brands.vue"), "Brands"),
    admin("/attributes", () => import("@/admin/catalog/Attributes.vue"), "Attributes & Specs"),
    admin("/reviews", () => import("@/admin/catalog/Reviews.vue"), "Reviews"),

    // Inventory
    admin("/inventory", () => import("@/admin/inventory/Stock.vue"), "Stock Overview"),
    admin("/inventory/movements", () => import("@/admin/inventory/Movements.vue"), "Stock Movements"),

    // Sales
    admin("/orders", () => import("@/admin/orders/Orders.vue"), "All Orders"),
    admin("/orders/new", () => import("@/admin/orders/OrderCreate.vue"), "Create Order"),
    admin("/orders/print", () => import("@/admin/orders/OrderPrint.vue"), "Print", { layout: "print" }),
    admin("/orders/:id(\\d+)", () => import("@/admin/orders/OrderDetail.vue"), "Order"),
    admin("/returns", () => import("@/admin/orders/Returns.vue"), "Returns & Refunds"),
    admin("/couriers", () => import("@/admin/orders/Couriers.vue"), "Couriers"),
    admin("/customers", () => import("@/admin/customers/Customers.vue"), "Customers"),
    admin("/customers/:id(\\d+)", () => import("@/admin/customers/CustomerDetail.vue"), "Customer"),
    admin("/profile", () => import("@/admin/Profile.vue"), "My Profile"),

    // Website content
    admin("/banners", () => import("@/admin/content/Banners.vue"), "Carousel & Banners"),
    admin("/announcements", () => import("@/admin/content/Announcements.vue"), "Announcement Bar"),
    admin("/home-sections", () => import("@/admin/content/HomeSections.vue"), "Homepage Sections"),
    admin("/pages", () => import("@/admin/content/Pages.vue"), "Pages"),
    admin("/pages/new", () => import("@/admin/content/PageEditor.vue"), "Add page"),
    admin("/pages/:id(\\d+)", () => import("@/admin/content/PageEditor.vue"), "Edit page"),
    admin("/messages", () => import("@/admin/content/Messages.vue"), "Contact Messages"),

    admin("/settings/store", settingsPage, "Store Information", { admin: true, section: "store" }),
    admin("/settings/appearance", settingsPage, "Appearance & Language", { admin: true, section: "appearance" }),
    admin("/settings/payments", settingsPage, "Payments", { admin: true, section: "payments" }),
    admin("/settings/shipping", () => import("@/admin/settings/Shipping.vue"), "Shipping", { admin: true }),
    admin("/settings/integrations", settingsPage, "Integrations & SEO", { admin: true, section: "integrations" }),
    admin("/settings/translations", () => import("@/admin/settings/Translations.vue"), "Language & Translations"),

    // ── Not found ──
    { path: "/admin/:pathMatch(.*)*", component: () => import("@/PageNotFound.vue"), meta: { layout: "admin", staff: true, title: "Page not found" } },
    // Information pages from Admin › Content › Pages (/about-us, /warranty-policy ...)
    { path: "/:slug([a-z0-9][a-z0-9-]*)", component: () => import("@/front/CmsPage.vue"), meta: { layout: "front" } },
    { path: "/:pathMatch(.*)*", component: () => import("@/PageNotFound.vue"), meta: { layout: "front", title: "Page not found" } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, saved) {
        if (saved) return saved;
        if (to.hash) return { el: to.hash, behavior: "smooth" };
        // Changing filters or sorting on the same page keeps the scroll position
        if (to.path === from.path) return false;
        return { left: 0, top: 0 };
    },
});

router.beforeEach((to) => {
    const isStaff = store.getters["auth/isStaff"];

    if (to.meta.staff && !isStaff) {
        return { path: "/admin/login", query: to.fullPath !== "/admin" ? { redirect: to.fullPath } : {} };
    }
    if (to.meta.admin && !store.getters["auth/isAdmin"]) {
        store.dispatch("notify", { text: t("Only admins can open this page."), type: "warning" });
        return { path: "/admin" };
    }
    if (to.meta.guest && to.path.startsWith("/admin") && isStaff) {
        return { path: "/admin" };
    }
    // Customer pages: sign in first, then come back
    if (to.meta.customer && !store.getters["auth/isLoggedIn"]) {
        return { path: "/login", query: { redirect: to.fullPath } };
    }
    if (to.meta.guest && !to.path.startsWith("/admin") && store.getters["auth/isLoggedIn"]) {
        return { path: isStaff ? "/admin" : "/account" };
    }

    store.commit("setLayout", to.meta.layout || "front");
    return true;
});

router.afterEach((to) => {
    const site = store.state.prefs.settings["store.name"] || "Tanjil Traders";
    document.title = to.meta.title ? `${t(to.meta.title)} · ${site}` : site;
});

export default router;
