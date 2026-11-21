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
import menu, { findMenuEntry } from "@/admin/menu.js";

const RECENT_KEY = "tt_recent_searches";

// Icons for the section tabs next to the clock (sidebar sub-pages have no icons of their own).
const TAB_ICONS = {
    "All Products": "mdi-package-variant-closed",
    "Add Product": "mdi-plus-box-outline",
    Categories: "mdi-shape-outline",
    Brands: "mdi-tag-outline",
    "Attributes & Specs": "mdi-tune-variant",
    Reviews: "mdi-star-outline",
    "Carousel & Banners": "mdi-view-carousel-outline",
    "Announcement Bar": "mdi-bullhorn-outline",
    "Homepage Sections": "mdi-view-dashboard-edit-outline",
    Pages: "mdi-file-document-outline",
    "Contact Messages": "mdi-email-outline",
    "Store Information": "mdi-storefront-outline",
    "Appearance & Language": "mdi-palette-outline",
    "Language & Translations": "mdi-translate",
    Payments: "mdi-credit-card-outline",
    "Integrations & SEO": "mdi-google",
};

export default {
    name: "HeaderNavigation",
    inject: ["hdr"],
    emits: ["toggle-sidebar"],

    data() {
        return {
            query: "",
            activeIndex: 0,
            recent: [],

        };
    },

    computed: {
        s() {
            return this.hdr.s;
        },

        // ── Page context: taken from the admin menu (admin/menu.js), so it always matches the sidebar ──
        menuEntry() {
            return findMenuEntry((this.$route && this.$route.path) || "", menu);
        },
        // The one breadcrumb in the admin, e.g. "Catalog / All Products / Edit product". Each part links to a real page.
        parentCrumbs() {
            const e = this.menuEntry;
            if (!e) return [];
            const list = [];
            if (e.group) {
                const first = e.group.children.find((c) => c.ready);
                if (first) list.push({ label: this.$t(e.group.title), to: first.to });
            }
            if (this.$t(e.item.title) !== this.pageTitle && e.item.to !== this.$route.path) {
                list.push({ label: this.$t(e.item.title), to: e.item.to });
            }
            return list;
        },
        pageTitle() {
            const r = this.$route || {};
            return this.$t((r.meta && r.meta.title) || (this.menuEntry && this.menuEntry.item.title) || "Dashboard");
        },
        // Every built admin page, for the command palette's "Go to" group
        pages() {
            return menu
                .filter((e) => !e.section)
                .flatMap((e) => (e.children ? e.children.map((c) => ({ ...c, icon: e.icon, group: e.title })) : [e]))
                .filter((p) => p.ready)
                .map((p) => ({ label: this.$t(p.title), to: p.to, icon: p.icon, hint: p.group ? this.$t(p.group) : "", words: `${p.title} ${p.group || ""}`.toLowerCase() }));
        },
        pageIcon() {
            const e = this.menuEntry;
            return (e && ((e.group && e.group.icon) || e.item.icon)) || "mdi-view-dashboard-outline";
        },

        // ── Section tabs next to the clock: the built pages of the current sidebar group ──
        currentSection() {
            const e = this.menuEntry;
            const tabs = e && e.group ? e.group.children.filter((c) => c.ready).map((c) => ({ label: this.$t(c.title), to: c.to, icon: TAB_ICONS[c.title] || e.group.icon })) : [];
            return { tabs: tabs.length > 1 ? tabs : [], stats: [], actions: [] };
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
                { label: "Products", icon: "mdi-package-variant-closed", color: "#0f9d6b", tint: "#dff5ec", to: "/admin/products", badge: a[1].count, tone: "warn" },
                { label: "Customers", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff", to: "/admin/customers" },
                { label: "Reports", icon: "mdi-chart-line", color: "#d97706", tint: "#fff1d1", to: "/admin/analytics/sales" },
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
                        { id: "s-products", label: "Products", hint: "Find in catalog", icon: "mdi-package-variant-closed", color: "#2f5be7", tint: "#e6edff", to: `/admin/products?q=${enc}`, remember: true },
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
