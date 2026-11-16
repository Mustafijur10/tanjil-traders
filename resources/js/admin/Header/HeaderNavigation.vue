<template>
    <!-- ═════════ Left: sidebar toggle + title + (clock slot) + section tabs ═════════ -->
    <div class="tt-left">
        <v-btn v-if="hdr.showSidebarToggle" icon variant="text" class="tt-icon-btn mr-1" aria-label="Toggle sidebar" @click="$emit('toggle-sidebar')">
            <v-icon size="20">mdi-menu</v-icon>
        </v-btn>

        <div class="tt-context">
            <span class="tt-page-tile" aria-hidden="true"><v-icon size="22" color="white">{{ pageIcon }}</v-icon></span>
            <div class="tt-title-row">
                <template v-for="c in parentCrumbs" :key="c.to">
                    <router-link :to="c.to" class="tt-title-parent">{{ c.label }}</router-link>
                    <span class="tt-title-parent">/</span>
                </template>
                <h1 class="tt-page-title">{{ pageTitle }}</h1>
            </div>
        </div>

        <!-- Clock / calendar / prayer / world-clock chip (HeaderClock) -->
        <slot />

        <!-- Section tabs + page actions + counters (or live mini stats) -->
        <div v-if="s.showTabs" class="tt-section">
            <nav v-if="currentTabs.length" class="tt-stabs" aria-label="Section">
                <router-link v-for="t in currentTabs" :key="t.to" :to="t.to" class="tt-stab" :class="{ 'is-on': activeTabTo === t.to }" :title="t.label">
                    <v-icon size="16">{{ t.icon }}</v-icon><span class="tt-stab-label">{{ t.label }}</span>
                </router-link>
            </nav>
            <div v-else class="tt-metrics">
                <span v-for="m in miniMetrics" :key="m.label" class="tt-mini">
                    <span class="tt-mini-ico" :style="{ background: m.tint }"><v-icon size="15" :color="m.color">{{ m.icon }}</v-icon></span>
                    <span class="tt-mini-text"><span class="tt-mini-val">{{ m.value }}</span><span class="tt-mini-lbl">{{ m.label }}</span></span>
                    <span class="tt-delta" :class="m.good ? 'up' : 'down'"><v-icon size="10">{{ m.delta >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(m.delta) }}%</span>
                </span>
            </div>

            <div v-if="s.showSectionActions && (currentSection.stats.length || currentSection.actions.length)" class="tt-sec-end">
                <div v-if="currentSection.stats.length" class="tt-sec-stats">
                    <span v-for="x in currentSection.stats" :key="x.label" class="tt-sstat">
                        <span class="tt-sstat-ico" :style="{ background: x.tint }"><v-icon size="14" :color="x.color">{{ x.icon }}</v-icon></span>
                        <b>{{ x.value }}</b><em>{{ x.label }}</em>
                    </span>
                </div>
                <div v-if="currentSection.actions.length" class="tt-sec-actions">
                    <button v-for="a in currentSection.actions" :key="a.label" type="button" class="tt-sact" :class="{ 'is-primary': a.primary }" :title="a.label" @click="runSectionAction(a)">
                        <v-icon size="16">{{ a.icon }}</v-icon><span class="tt-sact-label">{{ a.label }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════ Optional second bar (Settings → Header → Second bar) ═════════ -->
    <div v-if="s.showSecondBar && (s.showQuick || s.showStore || s.showPeriod)" class="tt-row2">
        <nav v-if="s.showQuick" class="tt-quick" aria-label="Quick links">
            <router-link v-for="q in quickLinks" :key="q.label" :to="q.to" class="tt-qchip" :class="{ 'is-on': isActiveLink(q.to) }" :title="q.label">
                <span class="tt-qico" :style="{ background: q.tint }"><v-icon size="15" :color="q.color">{{ q.icon }}</v-icon></span>
                <span class="tt-qlabel">{{ q.label }}</span>
                <em v-if="q.badge" class="tt-qbadge" :class="q.tone">{{ q.badge }}</em>
            </router-link>
        </nav>

        <div class="tt-tools-end">
            <v-menu v-if="s.showStore" location="bottom end" offset="8" transition="scale-transition">
                <template #activator="{ props }">
                    <button v-bind="props" type="button" class="tt-period" aria-label="Choose store">
                        <v-icon size="18" color="#2f5be7">mdi-storefront-outline</v-icon>
                        <span>{{ hdr.storeLabel }}</span>
                        <v-icon size="16" class="tt-chev">mdi-chevron-down</v-icon>
                    </button>
                </template>
                <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="268" flat>
                    <div class="tt-panel-label">Store / warehouse</div>
                    <div class="px-2 pb-2">
                        <button v-for="o in hdr.stores" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.store === o.value }" @click="hdr.setStore(o.value)">
                            <span class="tt-tile sm" style="background: #e6edff"><v-icon size="16" color="#2f5be7">{{ o.icon }}</v-icon></span>
                            <span class="flex-grow-1 text-left" style="min-width: 0">
                                <span class="tt-date-opt-text">{{ o.label }}</span>
                                <span class="tt-sub d-block">{{ o.hint }}</span>
                            </span>
                            <v-icon v-if="s.store === o.value" size="18" color="var(--h-green)">mdi-check-circle</v-icon>
                        </button>
                    </div>
                </v-card>
            </v-menu>

            <v-menu v-if="s.showPeriod" location="bottom end" offset="8" transition="scale-transition">
                <template #activator="{ props }">
                    <button v-bind="props" type="button" class="tt-period" aria-label="Choose date range">
                        <v-icon size="18" color="var(--h-green)">mdi-calendar-range</v-icon>
                        <span>{{ hdr.periodLabel }}</span>
                        <v-icon size="16" class="tt-chev">mdi-chevron-down</v-icon>
                    </button>
                </template>
                <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="264" flat>
                    <div class="tt-panel-label">Date range</div>
                    <div class="px-2 pb-2">
                        <button v-for="o in hdr.periodOptions" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.period === o.value }" @click="hdr.setPeriod(o.value)">
                            <span class="flex-grow-1 text-left" style="min-width: 0">
                                <span class="tt-date-opt-text" :lang="s.dateLang" :class="'is-' + s.dateLang">{{ o.label }}</span>
                                <span class="tt-sub d-block">{{ o.hint }}</span>
                            </span>
                            <v-icon v-if="s.period === o.value" size="18" color="var(--h-green)">mdi-check-circle</v-icon>
                        </button>
                    </div>
                </v-card>
            </v-menu>
        </div>
    </div>

    <!-- ═════════ Command palette (opened from the search button in HeaderMenus or with Ctrl K) ═════════ -->
    <v-dialog v-model="hdr.paletteOpen" max-width="640" content-class="tt-cmd-dialog" scrim="#053d35" @after-enter="focusInput">
        <v-card class="tt-cmd" :class="{ 'is-dark': hdr.isDark }" flat>
            <div class="tt-cmd-input">
                <v-icon size="20" class="tt-muted">mdi-magnify</v-icon>
                <input ref="cmdInput" v-model="query" type="text" placeholder="Search pages, actions, orders, customers…" aria-label="Search" autocomplete="off" @keydown="onCmdKey" />
                <button v-if="query" type="button" class="tt-clear" aria-label="Clear search" @click="query = ''"><v-icon size="16">mdi-close</v-icon></button>
                <kbd class="tt-kbd">Esc</kbd>
            </div>

            <div ref="cmdList" class="tt-cmd-list">
                <template v-for="g in paletteGroups" :key="g.title">
                    <div class="tt-cmd-group">{{ g.title }}</div>
                    <button
                        v-for="it in g.items"
                        :key="it.id"
                        type="button"
                        class="tt-cmd-item"
                        :class="{ 'is-active': flatItems[activeIndex] && flatItems[activeIndex].id === it.id }"
                        @mouseenter="activeIndex = flatItems.indexOf(it)"
                        @click="runItem(it)"
                    >
                        <span class="tt-tile" :style="{ background: it.tint || '#e3f6ee' }"><v-icon size="16" :color="it.color || 'var(--h-green)'">{{ it.icon }}</v-icon></span>
                        <span class="flex-grow-1 text-left">
                            <span class="tt-cmd-label">{{ it.label }}</span>
                            <span v-if="it.hint" class="tt-sub d-block">{{ it.hint }}</span>
                        </span>
                        <v-icon v-if="flatItems[activeIndex] && flatItems[activeIndex].id === it.id" size="16" class="tt-muted">mdi-subdirectory-arrow-left</v-icon>
                    </button>
                </template>
            </div>

            <div class="tt-cmd-foot">
                <span><kbd class="tt-kbd">↑</kbd><kbd class="tt-kbd">↓</kbd> navigate</span>
                <span><kbd class="tt-kbd">↵</kbd> select</span>
                <span><kbd class="tt-kbd">Esc</kbd> close</span>
                <v-spacer />
                <span class="tt-muted">{{ flatItems.length }} results</span>
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
/*
 * HeaderNavigation.vue: where you are and where you can go.
 * Page title and breadcrumbs, section tabs / stats / actions, the optional second bar
 * (quick links, store, date range) and the command palette.
 * Shared state and actions come from Header.vue through `hdr`.
 */
const RECENT_KEY = "tt_recent_searches";

export default {
    name: "HeaderNavigation",
    inject: ["hdr"],
    emits: ["toggle-sidebar"],

    data() {
        return {
            query: "",
            activeIndex: 0,
            recent: [],

            // ── Next to the clock: tabs for the current area, plus counters and actions. Routes match your sidebar. Stats are sample numbers: replace with API values. ──
            sectionTabs: [
                {
                    match: ["/admin/catalog", "/admin/product", "/admin/brand", "/admin/category"],
                    tabs: [
                        { label: "Products", to: "/admin/catalog", icon: "mdi-package-variant-closed" },
                        { label: "Brands", to: "/admin/brand", icon: "mdi-tag-outline" },
                        { label: "Categories", to: "/admin/category", icon: "mdi-shape-outline" },
                    ],
                    stats: [
                        { label: "Products", value: "2,146", icon: "mdi-package-variant-closed", color: "#2f5be7", tint: "#e6edff" },
                        { label: "Low stock", value: "18", icon: "mdi-archive-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Out of stock", value: "14", icon: "mdi-alert-circle-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [
                        { label: "Add product", icon: "mdi-plus", to: "/admin/product", primary: true },
                        { label: "Import", icon: "mdi-tray-arrow-down", to: "/admin/products/import" },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/orders", "/admin/create-order", "/admin/pending"],
                    tabs: [
                        { label: "All orders", to: "/admin/orders", icon: "mdi-receipt-text-outline" },
                        { label: "Pending", to: "/admin/pending", icon: "mdi-clock-outline" },
                        { label: "Processing", to: "/admin/orders/processing", icon: "mdi-progress-clock" },
                        { label: "Returns", to: "/admin/orders/returns", icon: "mdi-backup-restore" },
                    ],
                    stats: [
                        { label: "Orders today", value: "17", icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff" },
                        { label: "To confirm", value: "6", icon: "mdi-clock-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Unpaid", value: "4", icon: "mdi-cash-clock", color: "#7c3aed", tint: "#efe8ff" },
                    ],
                    actions: [
                        { label: "New order", icon: "mdi-plus", to: "/admin/create-order", primary: true },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/sales-analytics", "/admin/seo-analytics", "/admin/analytics", "/admin/sales-report", "/admin/inventory-report", "/admin/customer-report"],
                    tabs: [
                        { label: "Sales analytics", to: "/admin/sales-analytics", icon: "mdi-chart-line" },
                        { label: "SEO analytics", to: "/admin/seo-analytics", icon: "mdi-magnify-scan" },
                        { label: "Sales report", to: "/admin/sales-report", icon: "mdi-file-chart-outline" },
                        { label: "Customer report", to: "/admin/customer-report", icon: "mdi-account-group-outline" },
                    ],
                    stats: [
                        { label: "Revenue today", value: "৳ 48.2K", icon: "mdi-cash-multiple", color: "#0f9d6b", tint: "#dff5ec" },
                        { label: "Conversion", value: "4.4%", icon: "mdi-percent-outline", color: "#ec4899", tint: "#fde6f1" },
                    ],
                    actions: [
                        { label: "Export report", icon: "mdi-tray-arrow-up", fn: "exportCsv", primary: true },
                        { label: "Print", icon: "mdi-printer-outline", fn: "printPage" },
                    ],
                },
                {
                    match: ["/admin/customers", "/admin/reviews"],
                    tabs: [
                        { label: "All customers", to: "/admin/customers", icon: "mdi-account-group-outline" },
                        { label: "Groups", to: "/admin/customers/groups", icon: "mdi-account-multiple-outline" },
                        { label: "Reviews", to: "/admin/reviews", icon: "mdi-star-outline" },
                    ],
                    stats: [
                        { label: "Customers", value: "1,208", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff" },
                        { label: "New today", value: "9", icon: "mdi-account-plus-outline", color: "#0f9d6b", tint: "#dff5ec" },
                    ],
                    actions: [
                        { label: "Add customer", icon: "mdi-plus", to: "/admin/customers", primary: true },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/inventory"],
                    tabs: [
                        { label: "Stock levels", to: "/admin/inventory", icon: "mdi-warehouse" },
                        { label: "Low stock", to: "/admin/inventory/low-stock", icon: "mdi-archive-alert-outline" },
                        { label: "Suppliers", to: "/admin/inventory/suppliers", icon: "mdi-truck-outline" },
                    ],
                    stats: [
                        { label: "Low stock", value: "18", icon: "mdi-archive-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Out of stock", value: "14", icon: "mdi-alert-circle-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [{ label: "Export stock", icon: "mdi-tray-arrow-up", fn: "exportCsv", primary: true }],
                },
                {
                    match: ["/admin/coupons", "/admin/banners", "/admin/notifications"],
                    tabs: [
                        { label: "Coupons", to: "/admin/coupons", icon: "mdi-ticket-percent-outline" },
                        { label: "Banners", to: "/admin/banners", icon: "mdi-image-multiple-outline" },
                        { label: "Notifications", to: "/admin/notifications", icon: "mdi-bell-outline" },
                    ],
                    stats: [{ label: "Active coupons", value: "7", icon: "mdi-ticket-percent-outline", color: "#7c3aed", tint: "#efe8ff" }],
                    actions: [{ label: "New coupon", icon: "mdi-plus", to: "/admin/coupons", primary: true }],
                },
                {
                    match: ["/admin/shipping"],
                    tabs: [
                        { label: "Zones", to: "/admin/shipping", icon: "mdi-map-marker-radius-outline" },
                        { label: "Methods", to: "/admin/shipping/methods", icon: "mdi-truck-fast-outline" },
                    ],
                    stats: [
                        { label: "On-time", value: "94%", icon: "mdi-truck-check-outline", color: "#0f9d6b", tint: "#dff5ec" },
                        { label: "Delayed", value: "14", icon: "mdi-truck-alert-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [{ label: "Add method", icon: "mdi-plus", to: "/admin/shipping/methods", primary: true }],
                },
                {
                    match: ["/admin/general-settings", "/admin/storefront", "/admin/settings", "/admin/roles"],
                    tabs: [
                        { label: "General", to: "/admin/general-settings", icon: "mdi-cog-outline" },
                        { label: "Storefront", to: "/admin/storefront", icon: "mdi-storefront-outline" },
                        { label: "Payments", to: "/admin/settings/payment", icon: "mdi-credit-card-outline" },
                        { label: "Staff & roles", to: "/admin/roles", icon: "mdi-shield-account-outline" },
                    ],
                    stats: [],
                    actions: [{ label: "Help", icon: "mdi-lifebuoy", to: "/admin/help" }],
                },
            ],
        };
    },

    computed: {
        s() {
            return this.hdr.s;
        },

        // ── Page context ──
        crumbs() {
            const path = (this.$route && this.$route.path) || "";
            const parts = path.split("/").filter(Boolean);
            const trimmed = parts[0] === "admin" ? parts.slice(1) : parts;
            let acc = parts[0] === "admin" ? "/admin" : "";
            return trimmed.map((p) => {
                acc += "/" + p;
                return { label: p.replace(/[-_]/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()), to: acc };
            });
        },
        parentCrumbs() {
            return this.crumbs.slice(0, -1);
        },
        pageTitle() {
            const r = this.$route || {};
            const pretty = (x) => String(x).replace(/([a-z0-9])([A-Z])/g, "$1 $2").replace(/[-_]/g, " ").replace(/\s+/g, " ").trim();
            return pretty((r.meta && r.meta.title) || r.name || (this.crumbs.length ? this.crumbs[this.crumbs.length - 1].label : "Dashboard"));
        },
        // Pages are used for the palette's "Go to" group and to pick the title icon
        pages() {
            return [
                { label: "Dashboard", to: "/admin/dashboard", icon: "mdi-view-dashboard-outline", hint: "Overview of your store", words: "home overview kpi" },
                { label: "Sales analytics", to: "/admin/sales-analytics", icon: "mdi-chart-line", hint: "Revenue, funnel, forecast", words: "revenue reports funnel" },
                { label: "Orders", to: "/admin/orders", icon: "mdi-receipt-text-outline", hint: "Manage customer orders", words: "sales invoices" },
                { label: "Catalog", to: "/admin/catalog", icon: "mdi-package-variant-closed", hint: "Products and stock", words: "products inventory stock" },
                { label: "Brands", to: "/admin/brand", icon: "mdi-tag-outline", hint: "Manage brands", words: "samsung anker xiaomi" },
                { label: "Categories", to: "/admin/category", icon: "mdi-shape-outline", hint: "Catalog structure", words: "subcategory" },
                { label: "Customers", to: "/admin/customers", icon: "mdi-account-group-outline", hint: "Customer list and tiers", words: "clients buyers" },
                { label: "Account settings", to: this.hdr.accountRoute, icon: "mdi-account-cog-outline", hint: "Profile and password", words: "profile password" },
            ];
        },
        pageIcon() {
            const path = ((this.$route && this.$route.path) || "").toLowerCase();
            const hit = this.pages.find((p) => path.startsWith(p.to.toLowerCase()));
            if (hit) return hit.icon;
            const map = [["analytic", "mdi-chart-line"], ["order", "mdi-receipt-text-outline"], ["product", "mdi-package-variant-closed"], ["catalog", "mdi-package-variant-closed"], ["brand", "mdi-tag-outline"], ["categor", "mdi-shape-outline"], ["customer", "mdi-account-group-outline"], ["setting", "mdi-cog-outline"]];
            const m = map.find(([k]) => path.includes(k));
            return m ? m[1] : "mdi-view-dashboard-outline";
        },

        // ── Section area next to the clock ──
        currentSection() {
            const path = (this.$route && this.$route.path) || "";
            const sec = this.sectionTabs.find((x) => x.match.some((m) => path.startsWith(m)));
            return { tabs: (sec && sec.tabs) || [], stats: (sec && sec.stats) || [], actions: (sec && sec.actions) || [] };
        },
        currentTabs() {
            return this.currentSection.tabs;
        },
        // the tab whose route is the longest match for the current path (so "All orders" is not lit on /orders/processing)
        activeTabTo() {
            const path = (this.$route && this.$route.path) || "";
            let best = "";
            this.currentTabs.forEach((t) => {
                const base = t.to.split("?")[0];
                if ((path === base || path.startsWith(base + "/")) && base.length > best.length) best = t.to;
            });
            return best;
        },
        miniMetrics() {
            return this.hdr.glance.tiles.slice(0, 3);
        },

        // ── Second bar ──
        quickLinks() {
            const a = this.hdr.glance.attention;
            return [
                { label: "Orders", icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff", to: "/admin/orders", badge: a[0].count, tone: "" },
                { label: "Products", icon: "mdi-package-variant-closed", color: "#0f9d6b", tint: "#dff5ec", to: "/admin/catalog", badge: a[1].count, tone: "warn" },
                { label: "Customers", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff", to: "/admin/customers" },
                { label: "Reports", icon: "mdi-chart-line", color: "#d97706", tint: "#fff1d1", to: "/admin/sales-analytics" },
            ];
        },

        // ── Command palette ──
        paletteGroups() {
            const q = this.query.trim().toLowerCase();
            const groups = [];
            const hdr = this.hdr;
            const actions = [
                ...hdr.quickCreate.map((c, i) => ({ id: "qc" + i, label: c.label, hint: "Quick action", icon: c.icon, color: c.color, tint: c.tint, to: c.to })),
                { id: "a-settings", label: "Open settings sidebar", hint: "Header, display, time and data", icon: "mdi-tune-variant", color: "#0f9d6b", tint: "#dff5ec", run: () => hdr.openDrawer() },
                { id: "a-theme", label: hdr.isDark ? "Switch to light mode" : "Switch to dark mode", hint: "Appearance", icon: hdr.isDark ? "mdi-weather-sunny" : "mdi-weather-night", color: "#d97706", tint: "#fff1d1", run: () => hdr.toggleTheme() },
                { id: "a-fs", label: "Toggle full screen", hint: "Display", icon: "mdi-fullscreen", color: "#2f5be7", tint: "#e6edff", run: () => hdr.toggleFullscreen() },
                { id: "a-logout", label: "Log out", hint: "End your session", icon: "mdi-logout", color: "#dc2626", tint: "#fde8e8", run: () => hdr.logout() },
            ];
            const pages = this.pages.map((p, i) => ({ id: "pg" + i, label: p.label, hint: p.hint, icon: p.icon, to: p.to, words: p.words }));
            const match = (it) => !q || (it.label + " " + (it.hint || "") + " " + (it.words || "")).toLowerCase().includes(q);

            if (!q && this.recent.length) {
                groups.push({ title: "Recent searches", items: this.recent.map((t, i) => ({ id: "rc" + i, label: t, icon: "mdi-history", color: "#6b7f7b", tint: "#eef2f1", fill: t })) });
            }
            const a = actions.filter(match);
            if (a.length) groups.push({ title: "Quick actions", items: q ? a : a.slice(0, 6) });
            const p = pages.filter(match);
            if (p.length) groups.push({ title: "Go to", items: p });

            if (q) {
                const enc = encodeURIComponent(this.query.trim());
                groups.push({
                    title: `Search for “${this.query.trim()}”`,
                    items: [
                        { id: "s-orders", label: "Orders", hint: "Find in all orders", icon: "mdi-receipt-text-outline", color: "#0f9d6b", tint: "#dff5ec", to: `/admin/orders?search=${enc}`, remember: true },
                        { id: "s-products", label: "Products", hint: "Find in catalog", icon: "mdi-package-variant-closed", color: "#2f5be7", tint: "#e6edff", to: `/admin/catalog?search=${enc}`, remember: true },
                        { id: "s-customers", label: "Customers", hint: "Find by name, phone or email", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff", to: `/admin/customers?search=${enc}`, remember: true },
                    ],
                });
            }
            return groups;
        },
        flatItems() {
            return this.paletteGroups.flatMap((g) => g.items);
        },
    },

    watch: {
        query() {
            this.activeIndex = 0;
        },
        "hdr.paletteOpen"(v) {
            if (v) {
                this.query = "";
                this.activeIndex = 0;
                this.loadRecent();
            }
        },
        activeIndex() {
            this.$nextTick(() => {
                const el = this.$refs.cmdList && this.$refs.cmdList.querySelector(".tt-cmd-item.is-active");
                if (el) el.scrollIntoView({ block: "nearest" });
            });
        },
    },

    mounted() {
        this.loadRecent();
    },

    methods: {
        // ── Section actions (buttons beside the tabs) call the shared actions in Header.vue ──
        runSectionAction(a) {
            const fns = { exportCsv: () => this.hdr.exportReport("csv"), printPage: () => this.hdr.printPage() };
            if (a.fn && fns[a.fn]) fns[a.fn]();
            else if (a.to) this.hdr.go(a.to);
        },
        isActiveLink(to) {
            const path = (this.$route && this.$route.path) || "";
            return path === to || path.startsWith(to + "/");
        },

        // ── Command palette ──
        focusInput() {
            this.$nextTick(() => this.$refs.cmdInput && this.$refs.cmdInput.focus());
        },
        onCmdKey(e) {
            const n = this.flatItems.length;
            if (e.key === "ArrowDown") {
                e.preventDefault();
                this.activeIndex = n ? (this.activeIndex + 1) % n : 0;
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                this.activeIndex = n ? (this.activeIndex - 1 + n) % n : 0;
            } else if (e.key === "Enter") {
                e.preventDefault();
                const it = this.flatItems[this.activeIndex];
                if (it) this.runItem(it);
            }
        },
        runItem(it) {
            if (it.fill) {
                this.query = it.fill;
                this.focusInput();
                return;
            }
            if (it.remember) this.addRecent(this.query.trim());
            this.hdr.closePalette();
            if (it.to) this.hdr.go(it.to);
            else if (typeof it.run === "function") it.run();
        },
        loadRecent() {
            try {
                this.recent = JSON.parse(window.localStorage.getItem(RECENT_KEY) || "[]").slice(0, 4);
            } catch (e) {
                this.recent = [];
            }
        },
        addRecent(term) {
            if (!term) return;
            const next = [term, ...this.recent.filter((t) => t !== term)].slice(0, 5);
            this.recent = next;
            try {
                window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
            } catch (e) {
                /* storage unavailable */
            }
        },
    },
};
</script>
