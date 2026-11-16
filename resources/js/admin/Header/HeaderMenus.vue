<template>
    <!-- ═════════ Right: search, today, create, tools, user ═════════ -->
    <div class="tt-right">
        <button v-if="s.showSearch" type="button" class="tt-search" aria-label="Open search" @click="hdr.openPalette()">
            <v-icon size="18" class="tt-search-icon">mdi-magnify</v-icon>
            <span class="tt-search-text">Search <transition name="tt-fade" mode="out-in"><b :key="hintIndex" class="tt-hint">{{ searchHints[hintIndex] }}</b></transition>…</span>
            <v-tooltip activator="parent" location="bottom">Search · Ctrl K</v-tooltip>
        </button>

        <!-- Today at a glance -->
        <v-menu v-if="s.showToday" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
            <template #activator="{ props }">
                <button v-bind="props" type="button" class="tt-today" aria-label="Today's sales snapshot">
                    <span class="tt-ring" :style="{ background: `conic-gradient(var(--h-green) ${glance.targetPct * 3.6}deg, var(--h-line) 0deg)` }">
                        <span class="tt-ring-in"><v-icon size="14" color="var(--h-green)">mdi-trending-up</v-icon></span>
                    </span>
                    <span class="tt-today-text">
                        <span class="tt-today-label">Today's sales</span>
                        <span class="tt-today-value">{{ shortMoney(glance.revenue) }}<span class="tt-delta up"><v-icon size="10">mdi-arrow-up</v-icon>{{ glance.revenueDelta }}%</span></span>
                    </span>
                    <v-tooltip activator="parent" location="bottom">Today's sales so far · the ring shows {{ glance.targetPct }}% of this month's target</v-tooltip>
                </button>
            </template>
            <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="392" flat>
                <div class="tt-glance-head">
                    <div>
                        <div class="tt-panel-title">Today at a glance</div>
                        <div class="tt-sub">{{ dateLabel }} · updated {{ timeLabel }}</div>
                    </div>
                    <v-spacer />
                    <span class="tt-pill-live"><span class="tt-pill-dot"></span>Live</span>
                </div>

                <div class="tt-glance-grid">
                    <div v-for="t in glance.tiles" :key="t.label" class="tt-gtile">
                        <div class="d-flex align-center justify-space-between">
                            <span class="tt-tile sm" :style="{ background: t.tint }"><v-icon size="15" :color="t.color">{{ t.icon }}</v-icon></span>
                            <span class="tt-delta" :class="t.good ? 'up' : 'down'"><v-icon size="10">{{ t.delta >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(t.delta) }}%</span>
                        </div>
                        <div class="tt-gvalue">{{ t.value }}</div>
                        <div class="tt-sub">{{ t.label }}</div>
                    </div>
                </div>

                <div class="tt-glance-section">
                    <div class="d-flex justify-space-between align-center mb-1">
                        <span class="tt-glabel">Monthly target</span>
                        <span class="tt-gpct">{{ glance.targetPct }}%</span>
                    </div>
                    <div class="tt-bar"><div class="tt-bar-fill" :style="{ width: glance.targetPct + '%' }"></div></div>
                    <div class="tt-sub mt-1">{{ shortMoney(glance.monthRevenue) }} of {{ shortMoney(glance.monthTarget) }} · {{ daysLeft }} days left</div>
                </div>

                <div class="tt-glance-section">
                    <div class="d-flex justify-space-between align-center mb-2">
                        <span class="tt-glabel">Orders by hour</span>
                        <span class="tt-sub">Peak {{ glance.peakHour }}</span>
                    </div>
                    <div class="tt-bars">
                        <span v-for="(v, i) in glance.hourly" :key="i" class="tt-bar-col" :class="{ 'is-peak': v === hourlyMax }" :style="{ height: (v / hourlyMax) * 100 + '%' }" :title="v + ' orders'"></span>
                    </div>
                </div>

                <div class="tt-glance-section">
                    <div class="tt-glabel mb-2">Needs attention</div>
                    <button v-for="a in glance.attention" :key="a.label" type="button" class="tt-attn-row" @click="hdr.go(a.to)">
                        <span class="tt-tile sm" :style="{ background: a.tint }"><v-icon size="15" :color="a.color">{{ a.icon }}</v-icon></span>
                        <span class="flex-grow-1 text-left">{{ a.label }}</span>
                        <span class="tt-attn-count" :style="{ color: a.color, background: a.tint }">{{ a.count }}</span>
                    </button>
                </div>

                <button type="button" class="tt-panel-foot" @click="hdr.go('/admin/sales-analytics')">Open sales analytics <v-icon size="14">mdi-arrow-right</v-icon></button>
            </v-card>
        </v-menu>

        <!-- Quick create -->
        <v-menu v-if="s.showCreate" location="bottom end" offset="10" transition="scale-transition">
            <template #activator="{ props }">
                <v-btn v-bind="props" variant="flat" class="text-none tt-create-btn" prepend-icon="mdi-plus">
                    <span class="tt-create-label">Create</span>
                    <v-tooltip activator="parent" location="bottom">Create new order, product, brand…</v-tooltip>
                </v-btn>
            </template>
            <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="248" flat>
                <div class="tt-panel-label">Quick create</div>
                <div class="px-2 pb-2">
                    <button v-for="q in hdr.quickCreate" :key="q.label" type="button" class="tt-menu-item" @click="hdr.go(q.to)">
                        <span class="tt-tile" :style="{ background: q.tint }"><v-icon size="16" :color="q.color">{{ q.icon }}</v-icon></span>
                        <span class="flex-grow-1 text-left">{{ q.label }}</span>
                        <v-icon size="14" class="tt-muted">mdi-arrow-top-right</v-icon>
                    </button>
                </div>
            </v-card>
        </v-menu>

        <!-- Tools cluster: fullscreen, info center, quick settings (slot, from HeaderSettings), notifications -->
        <div class="tt-actions">
            <v-btn v-if="s.showFullscreen" icon variant="text" class="tt-icon-btn tt-hide-xs" :aria-label="hdr.isFullscreen ? 'Exit full screen' : 'Enter full screen'" @click="hdr.toggleFullscreen()">
                <v-icon size="20">{{ hdr.isFullscreen ? "mdi-fullscreen-exit" : "mdi-fullscreen" }}</v-icon>
                <v-tooltip activator="parent" location="bottom">{{ hdr.isFullscreen ? "Exit full screen" : "Full screen" }}</v-tooltip>
            </v-btn>

            <!-- Info center -->
            <v-menu v-if="s.showInfo" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                <template #activator="{ props }">
                    <v-btn v-bind="props" icon variant="text" class="tt-icon-btn" aria-label="Info center">
                        <v-badge v-if="infoWarnings" dot color="#d97706"><v-icon size="20">mdi-information-outline</v-icon></v-badge>
                        <v-icon v-else size="20">mdi-information-outline</v-icon>
                        <v-tooltip activator="parent" location="bottom">Info center</v-tooltip>
                    </v-btn>
                </template>
                <v-card class="tt-panel tt-info" :class="{ 'is-dark': hdr.isDark }" width="360" flat>
                    <div class="tt-panel-head">
                        <span class="tt-set-ico"><v-icon size="18" color="var(--h-green)">mdi-information-outline</v-icon></span>
                        <div>
                            <div class="tt-panel-title">Info center</div>
                            <div class="tt-sub">Status and quick reference</div>
                        </div>
                    </div>

                    <div v-if="s.info.status" class="tt-info-block">
                        <div class="tt-glabel mb-1">System status</div>
                        <div v-for="r in infoStatus" :key="r.label" class="tt-info-row">
                            <span class="tt-info-ico" :class="'tone-' + r.tone"><v-icon size="15">{{ r.icon }}</v-icon></span>
                            <span class="flex-grow-1 text-left">{{ r.label }}</span>
                            <span class="tt-info-val" :class="'tone-' + r.tone">{{ r.value }}</span>
                        </div>
                    </div>

                    <div v-if="s.info.rates" class="tt-info-block">
                        <div class="d-flex justify-space-between align-center mb-2"><span class="tt-glabel">Exchange rates</span><span class="tt-sub">to ৳ BDT · sample</span></div>
                        <div class="tt-rates">
                            <span v-for="r in infoRates" :key="r.code" class="tt-rate"><b>{{ r.code }}</b>{{ r.value }}</span>
                        </div>
                    </div>

                    <div v-if="s.info.session" class="tt-info-block">
                        <div class="tt-glabel mb-1">This session</div>
                        <div class="tt-kv"><span>Signed in as</span><b>{{ user.name }} · {{ user.role }}</b></div>
                        <div class="tt-kv"><span>Time zone</span><b>{{ hdr.tzLabel }}</b></div>
                        <div class="tt-kv"><span>Store</span><b>{{ hdr.storeLabel }}</b></div>
                        <div class="tt-kv"><span>Date range</span><b>{{ hdr.periodLabel }}</b></div>
                    </div>

                    <div v-if="s.info.shortcuts" class="tt-info-block">
                        <div class="tt-glabel mb-2">Shortcuts</div>
                        <div class="tt-kv"><span>Search</span><span><kbd class="tt-kbd">Ctrl</kbd><kbd class="tt-kbd">K</kbd></span></div>
                        <div class="tt-kv"><span>Settings sidebar</span><span><kbd class="tt-kbd">Ctrl</kbd><kbd class="tt-kbd">,</kbd></span></div>
                    </div>

                    <button type="button" class="tt-panel-foot" @click="hdr.openDrawer('header')">Choose what shows here <v-icon size="14">mdi-arrow-right</v-icon></button>
                </v-card>
            </v-menu>

            <!-- Quick settings (HeaderSettings) -->
            <slot name="tools" />

            <!-- Notifications -->
            <v-menu v-if="s.showNotif" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                <template #activator="{ props }">
                    <v-btn v-bind="props" icon variant="text" class="tt-icon-btn" aria-label="Notifications">
                        <v-badge v-if="unreadCount" :content="unreadCount" color="#ec4899" floating class="tt-badge">
                            <v-icon size="20">mdi-bell-outline</v-icon>
                        </v-badge>
                        <v-icon v-else size="20">mdi-bell-outline</v-icon>
                    </v-btn>
                </template>
                <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="392" flat>
                    <div class="tt-panel-head">
                        <div>
                            <div class="tt-panel-title">Notifications</div>
                            <div class="tt-sub">{{ unreadCount ? unreadCount + " unread" : "You're all caught up" }}</div>
                        </div>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none tt-link-btn" :disabled="!unreadCount" @click="markAllRead">Mark all read</v-btn>
                    </div>

                    <div class="tt-chip-row">
                        <button v-for="t in notifTabs" :key="t.value" type="button" class="tt-chip" :class="{ 'is-on': notifTab === t.value }" @click="notifTab = t.value">{{ t.label }}</button>
                    </div>

                    <div class="tt-notif-list">
                        <button v-for="n in filteredNotifications" :key="n.id" type="button" class="tt-notif" :class="{ 'is-unread': !n.read }" @click="openNotification(n)">
                            <span class="tt-tile" :style="{ background: n.tint }"><v-icon size="17" :color="n.color">{{ n.icon }}</v-icon></span>
                            <span class="flex-grow-1 text-left">
                                <span class="tt-notif-title">{{ n.title }}</span>
                                <span class="tt-sub d-block">{{ n.desc }}</span>
                            </span>
                            <span class="tt-notif-time">{{ n.time }}<span v-if="!n.read" class="tt-unread-dot"></span></span>
                        </button>
                        <div v-if="!filteredNotifications.length" class="tt-empty">
                            <v-icon size="28" color="#9db3ad">mdi-bell-check-outline</v-icon>
                            <div>Nothing here yet</div>
                        </div>
                    </div>

                    <button type="button" class="tt-panel-foot" @click="hdr.go('/admin/notifications')">View all notifications <v-icon size="14">mdi-arrow-right</v-icon></button>
                </v-card>
            </v-menu>
        </div>

        <div class="tt-divider"></div>

        <!-- User -->
        <v-menu location="bottom end" offset="10" transition="scale-transition">
            <template #activator="{ props }">
                <button v-bind="props" type="button" class="tt-user" aria-label="Account menu">
                    <span class="tt-avatar">{{ initials }}<span class="tt-avatar-dot"></span></span>
                    <span class="tt-user-text tt-hide-md">
                        <span class="tt-user-name">{{ user.name }}</span>
                        <span class="tt-user-role">{{ user.role }}</span>
                    </span>
                    <v-icon size="16" class="tt-muted tt-hide-md">mdi-chevron-down</v-icon>
                </button>
            </template>
            <v-card class="tt-panel" :class="{ 'is-dark': hdr.isDark }" width="272" flat>
                <div class="tt-user-card">
                    <span class="tt-avatar lg">{{ initials }}</span>
                    <div style="min-width: 0">
                        <div class="tt-uc-greet">{{ greeting }}</div>
                        <div class="tt-uc-name text-truncate">{{ user.name }}</div>
                        <div class="tt-uc-mail text-truncate">{{ user.email || user.role }}</div>
                    </div>
                </div>
                <div class="pa-2">
                    <button type="button" class="tt-menu-item" @click="hdr.go(hdr.accountRoute)">
                        <v-icon size="18">mdi-account-cog-outline</v-icon><span class="flex-grow-1 text-left">Account settings</span>
                    </button>
                    <button type="button" class="tt-menu-item" @click="hdr.openDrawer()">
                        <v-icon size="18">mdi-tune-variant</v-icon><span class="flex-grow-1 text-left">Header &amp; display settings</span>
                    </button>
                    <button type="button" class="tt-menu-item" @click="hdr.toggleTheme()">
                        <v-icon size="18">{{ hdr.isDark ? "mdi-weather-sunny" : "mdi-weather-night" }}</v-icon><span class="flex-grow-1 text-left">{{ hdr.isDark ? "Switch to light mode" : "Switch to dark mode" }}</span>
                    </button>
                    <button type="button" class="tt-menu-item" @click="hdr.go('/admin/help')">
                        <v-icon size="18">mdi-lifebuoy</v-icon><span class="flex-grow-1 text-left">Help &amp; support</span>
                    </button>
                    <div class="tt-menu-sep"></div>
                    <button type="button" class="tt-menu-item danger" :disabled="hdr.loggingOut" @click="hdr.logout()">
                        <v-progress-circular v-if="hdr.loggingOut" indeterminate size="16" width="2" />
                        <v-icon v-else size="18">mdi-logout</v-icon>
                        <span class="flex-grow-1 text-left">Log out</span>
                    </button>
                </div>
            </v-card>
        </v-menu>
    </div>
</template>

<script>
/*
 * HeaderMenus.vue: the right-hand cluster.
 * Search button, Today's sales, Create, fullscreen, Info center, Notifications and the account menu.
 * The Quick settings gear is rendered in the `tools` slot by Header.vue (HeaderSettings).
 */
export default {
    name: "HeaderMenus",
    inject: ["hdr"],

    data() {
        return {
            hintIndex: 0,
            searchHints: ["orders", "customers", "products", "brands", "invoices", "coupons"],
            timer: null,

            notifTab: "all",
            notifTabs: [
                { label: "All", value: "all" },
                { label: "Orders", value: "orders" },
                { label: "Stock", value: "stock" },
                { label: "System", value: "system" },
            ],
            // ── Sample notifications: replace with GET /api/notifications ──
            notifications: [
                { id: 1, type: "orders", title: "New order #TT-10482", desc: "Rahim Traders · ৳ 48,500 · Paid via bKash", time: "2m", icon: "mdi-receipt-text-outline", color: "#0f9d6b", tint: "#dff5ec", read: false, to: "/admin/orders" },
                { id: 2, type: "stock", title: "Low stock: Anker PowerCore 20000", desc: "Only 3 units left across all warehouses", time: "18m", icon: "mdi-alert-outline", color: "#d97706", tint: "#fff1d1", read: false, to: "/admin/catalog" },
                { id: 3, type: "orders", title: "Delivery delayed — Khulna", desc: "14 parcels stuck for more than 4 days", time: "1h", icon: "mdi-truck-alert-outline", color: "#dc2626", tint: "#fde8e8", read: false, to: "/admin/orders" },
                { id: 4, type: "orders", title: "Payment received", desc: "Nusrat Jahan · ৳ 12,900 via Nagad", time: "3h", icon: "mdi-cash-check", color: "#2f5be7", tint: "#e6edff", read: true, to: "/admin/orders" },
                { id: 5, type: "system", title: "Nightly backup completed", desc: "Database snapshot saved successfully", time: "6h", icon: "mdi-database-check-outline", color: "#7c3aed", tint: "#efe8ff", read: true, to: null },
            ],

            // ── Sample info center data: replace with GET /api/admin/system-status and a rates endpoint ──
            infoStatus: [
                { label: "Storefront", value: "Online", tone: "ok", icon: "mdi-storefront-outline" },
                { label: "Payments (bKash, Nagad, cards)", value: "All working", tone: "ok", icon: "mdi-credit-card-check-outline" },
                { label: "Courier API", value: "Slow", tone: "warn", icon: "mdi-truck-alert-outline" },
                { label: "Last backup", value: "6h ago", tone: "ok", icon: "mdi-database-check-outline" },
            ],
            infoRates: [
                { code: "USD", value: "122.40" },
                { code: "SAR", value: "32.64" },
                { code: "AED", value: "33.33" },
                { code: "CNY", value: "16.90" },
                { code: "GBP", value: "158.10" },
                { code: "EUR", value: "135.20" },
            ],
        };
    },

    computed: {
        s() {
            return this.hdr.s;
        },
        glance() {
            return this.hdr.glance;
        },
        hourlyMax() {
            return Math.max(...this.glance.hourly);
        },
        infoWarnings() {
            return this.s.info.status ? this.infoStatus.filter((r) => r.tone !== "ok").length : 0;
        },
        greeting() {
            const h = this.hdr.now.getHours();
            return h < 12 ? "Good morning" : h < 17 ? "Good afternoon" : "Good evening";
        },
        dateLabel() {
            return this.hdr.now.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" });
        },
        timeLabel() {
            return this.hdr.fmtTime(this.hdr.clock, { locale: "en-US" });
        },
        daysLeft() {
            const d = this.hdr.now;
            return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate() - d.getDate();
        },

        // ── User ──
        user() {
            let u = {};
            try {
                const raw = JSON.parse(window.localStorage.getItem("user") || "{}") || {};
                u = raw.user || raw;
            } catch (e) {
                u = {};
            }
            return { name: u.name || u.username || "Admin", email: u.email || "", role: u.role || u.role_name || "Administrator" };
        },
        initials() {
            return this.user.name
                .split(" ")
                .filter(Boolean)
                .map((w) => w[0])
                .slice(0, 2)
                .join("")
                .toUpperCase();
        },

        // ── Notifications ──
        unreadCount() {
            return this.notifications.filter((n) => !n.read).length;
        },
        filteredNotifications() {
            return this.notifTab === "all" ? this.notifications : this.notifications.filter((n) => n.type === this.notifTab);
        },
    },

    mounted() {
        this.timer = setInterval(() => (this.hintIndex = (this.hintIndex + 1) % this.searchHints.length), 3200);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },

    methods: {
        shortMoney(v) {
            const f = (n) => String(Math.round(n * 10) / 10);
            if (v >= 10000000) return "৳ " + f(v / 10000000) + "Cr";
            if (v >= 100000) return "৳ " + f(v / 100000) + "L";
            if (v >= 1000) return "৳ " + f(v / 1000) + "K";
            return "৳ " + v;
        },
        openNotification(n) {
            n.read = true;
            if (n.to) this.hdr.go(n.to);
        },
        markAllRead() {
            this.notifications.forEach((n) => (n.read = true));
        },
    },
};
</script>
