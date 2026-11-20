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

    // ── Admin sign-in ──
    { path: "/admin/login", component: () => import("@/admin/auth/Login.vue"), meta: { layout: "auth", guest: true, title: "Sign in" } },
    { path: "/admin/forgot-password", component: () => import("@/admin/auth/ForgotPassword.vue"), meta: { layout: "auth", guest: true, title: "Forgot password" } },

    // ── Admin ──
    admin("", () => import("@/admin/DashBoard.vue"), "Dashboard"),
    admin("/settings/store", settingsPage, "Store Information", { admin: true, section: "store" }),
    admin("/settings/appearance", settingsPage, "Appearance & Language", { admin: true, section: "appearance" }),
    admin("/settings/payments", settingsPage, "Payments", { admin: true, section: "payments" }),
    admin("/settings/integrations", settingsPage, "Integrations & SEO", { admin: true, section: "integrations" }),
    admin("/settings/translations", () => import("@/admin/settings/Translations.vue"), "Language & Translations"),

    // ── Not found ──
    { path: "/admin/:pathMatch(.*)*", component: () => import("@/PageNotFound.vue"), meta: { layout: "admin", staff: true, title: "Page not found" } },
    { path: "/:pathMatch(.*)*", component: () => import("@/PageNotFound.vue"), meta: { layout: "front", title: "Page not found" } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, saved) {
        if (saved) return saved;
        if (to.hash) return { el: to.hash, behavior: "smooth" };
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

    store.commit("setLayout", to.meta.layout || "front");
    return true;
});

router.afterEach((to) => {
    const site = store.state.prefs.settings["store.name"] || "Tanjil Traders";
    document.title = to.meta.title ? `${t(to.meta.title)} · ${site}` : site;
});

export default router;
