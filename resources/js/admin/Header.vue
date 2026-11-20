<template>
    <header class="tt-header" :class="{ 'is-dark': isDark, 'is-compact': s.compact }" role="banner">
        <div class="tt-inner">
            <!-- Left side + optional second bar + command palette. The clock chip is slotted in beside the page title. -->
            <HeaderNavigation @toggle-sidebar="$emit('toggle-sidebar')">
                <HeaderClock @date-select="$emit('date-select', $event)" />
            </HeaderNavigation>

            <!-- Right side. Quick settings (gear) is slotted into the tools cluster. -->
            <HeaderMenus>
                <template #tools><HeaderSettings /></template>
            </HeaderMenus>
        </div>
        <div class="tt-header-line"></div>
        <v-snackbar v-model="toastOpen" color="#0f9d6b" timeout="2400" location="bottom">{{ toastText }}</v-snackbar>
    </header>
</template>

<script>
/*
 * Header.vue: the coordinator.
 * Owns every piece of state shared by the header (settings object `s`, theme, clock tick, drawer/palette open flags,
 * toast) and every shared action (theme, language, time format, store, period, export, logout, ...).
 * Child components get this instance through provide/inject as `hdr`, so each setting has ONE source of truth
 * and any button in any menu calls the same method.
 */
import HeaderNavigation from "./Header/HeaderNavigation.vue";
import HeaderClock from "./Header/HeaderClock.vue";
import HeaderMenus from "./Header/HeaderMenus.vue";
import HeaderSettings from "./Header/HeaderSettings.vue";

// Theme and language live in the global store (store/prefs.js) so they apply to every page.
// The other header options are saved in this browser and on the signed-in account.
const SETTINGS_KEY = "tt_header_settings_v2";
const ACCENTS = [
    { value: "green", label: "Emerald", hex: "#0f9d6b" },
    { value: "blue", label: "Blue", hex: "#2f5be7" },
    { value: "violet", label: "Violet", hex: "#7c3aed" },
    { value: "rose", label: "Rose", hex: "#e11d48" },
    { value: "amber", label: "Amber", hex: "#d97706" },
    { value: "teal", label: "Teal", hex: "#0f766e" },
];
const PERIOD_LABELS = {
    en: { today: "Today", yesterday: "Yesterday", "7d": "Last 7 days", "30d": "Last 30 days", month: "This month", year: "This year" },
    bn: { today: "আজ", yesterday: "গতকাল", "7d": "গত ৭ দিন", "30d": "গত ৩০ দিন", month: "এই মাস", year: "এই বছর" },
    ar: { today: "اليوم", yesterday: "أمس", "7d": "آخر 7 أيام", "30d": "آخر 30 يومًا", month: "هذا الشهر", year: "هذه السنة" },
};

// Every user setting lives in one object, so it is saved, restored and reset in one place.
const makeDefaults = (weekStart, hijriShift) => ({
    dateLang: "en",
    hour12: true,
    showSeconds: true,
    compact: false,
    accent: "green",
    // header modules
    showClock: true,
    showDateInChip: true,
    showWaqt: true,
    showFastChip: true,
    showTabs: true,
    showSectionActions: true,
    showSearch: true,
    showToday: true,
    showCreate: true,
    showFullscreen: true,
    showInfo: true,
    showNotif: true,
    showSecondBar: false,
    showQuick: true,
    showStore: true,
    showPeriod: true,
    showHandle: true,
    // calendar
    weekStart,
    hijriShift,
    showBij: true,
    showSunnah: true,
    showFastCard: true,
    showEvents: true,
    showAltDay: true,
    // prayer: minutes to add/subtract so times match your local mosque / Islamic Foundation timetable
    prayerCity: "Dhaka",
    showNafl: true,
    adjust: { sehri: 0, iftar: 0, fajr: 0, sunrise: 0, ishraq: 0, chasht: 0, zawal: 0, dhuhr: 0, asr: 0, maghrib: 0, awabin: 0, isha: 0, tahajjud: 0 },
    // data view
    store: "all",
    period: "7d",
    comparePrev: false,
    // info center blocks
    info: { status: true, rates: true, session: true, shortcuts: true },
});

export default {
    name: "AppHeader",

    components: { HeaderNavigation, HeaderClock, HeaderMenus, HeaderSettings },

    provide() {
        return { hdr: this };
    },

    props: {
        // Show a hamburger in the header (useful on mobile). Listen with @toggle-sidebar.
        showSidebarToggle: { type: Boolean, default: false },
        // Hijri date follows Umm al-Qura. Use -1 if local moon sighting runs a day behind (common in Bangladesh).
        hijriOffset: { type: Number, default: 0 },
        // First day of the calendar week (0 = Sunday … 6 = Saturday) and which weekdays are tinted as weekend.
        weekStart: { type: Number, default: 6 },
        weekendDays: { type: Array, default: () => [5] },
    },
    emits: ["toggle-sidebar", "theme-change", "date-select", "language-change", "period-change", "store-change", "compare-change", "export"],

    data() {
        return {
            s: makeDefaults(this.weekStart, this.hijriOffset),
            clock: new Date(),
            now: new Date(),
            timers: [],

            toastOpen: false,
            toastText: "",
            drawerOpen: false,
            drawerTab: "header",
            paletteOpen: false,
            isFullscreen: false,
            loggingOut: false,

            accountRoute: "/admin/profile",
            accentOptions: ACCENTS,
            languages: [
                { value: "en", name: "English" },
                { value: "bn", name: "বাংলা" },
            ],
            // Country flags used for the language switcher (English: UK, Bangla: Bangladesh, Arabic: Saudi Arabia)
            flagSvg: {
                en: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#012169"/><path d="M0 0L60 40M60 0L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0L60 40M60 0L0 40" stroke="#C8102E" stroke-width="3"/><path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="13"/><path d="M30 0V40M0 20H60" stroke="#C8102E" stroke-width="8"/></svg>',
                bn: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#006a4e"/><circle cx="27" cy="20" r="12" fill="#f42a41"/></svg>',
                ar: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#006c35"/><rect x="14" y="11" width="32" height="3.2" rx="1.6" fill="#fff"/><rect x="19" y="17" width="22" height="2.4" rx="1.2" fill="#fff"/><rect x="12" y="28" width="36" height="2.2" rx="1.1" fill="#fff"/><circle cx="14" cy="29" r="1.6" fill="#fff"/></svg>',
            },
            stores: [
                { value: "all", label: "All stores", hint: "Combined view", icon: "mdi-store-outline" },
                { value: "dhaka", label: "Dhaka showroom", hint: "Main branch", icon: "mdi-storefront-outline" },
                { value: "ctg", label: "Chattogram branch", hint: "Port city outlet", icon: "mdi-storefront-outline" },
                { value: "online", label: "Online store", hint: "Website and marketplaces", icon: "mdi-web" },
            ],
            // Used by the prayer tab (clock panel) and the Time tab (settings drawer)
            prayerCities: [
                { name: "Dhaka", lat: 23.8103, lng: 90.4125 },
                { name: "Chattogram", lat: 22.3569, lng: 91.7832 },
                { name: "Sylhet", lat: 24.8949, lng: 91.8687 },
                { name: "Khulna", lat: 22.8456, lng: 89.5403 },
                { name: "Rajshahi", lat: 24.3745, lng: 88.6042 },
                { name: "Barishal", lat: 22.701, lng: 90.3535 },
                { name: "Rangpur", lat: 25.7439, lng: 89.2752 },
                { name: "Mymensingh", lat: 24.7471, lng: 90.4203 },
            ],

            // ── Sample snapshot: replace with GET /api/admin/dashboard/today (read by Today panel, mini stats and quick links) ──
            glance: {
                revenue: 48200,
                revenueDelta: 12.4,
                monthRevenue: 960000,
                monthTarget: 1200000,
                targetPct: 80,
                peakHour: "8–9 PM",
                hourly: [2, 1, 1, 3, 5, 8, 6, 9, 7, 10, 12, 9, 14, 11, 8, 13, 16, 12],
                tiles: [
                    { label: "Orders today", value: "17", delta: 8.2, good: true, icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff" },
                    { label: "Avg. order value", value: "৳ 2,835", delta: 3.4, good: true, icon: "mdi-basket-outline", color: "#d97706", tint: "#fff1d1" },
                    { label: "Conversion rate", value: "4.4%", delta: 0.6, good: true, icon: "mdi-percent-outline", color: "#ec4899", tint: "#fde6f1" },
                    { label: "New customers", value: "9", delta: -2.1, good: false, icon: "mdi-account-plus-outline", color: "#7c3aed", tint: "#efe8ff" },
                ],
                attention: [
                    { label: "Orders awaiting confirmation", count: 6, icon: "mdi-clock-alert-outline", color: "#d97706", tint: "#fff1d1", to: "/admin/orders" },
                    { label: "Low-stock products", count: 18, icon: "mdi-archive-alert-outline", color: "#dc2626", tint: "#fde8e8", to: "/admin/catalog" },
                    { label: "Pending payments", count: 4, icon: "mdi-cash-clock", color: "#2f5be7", tint: "#e6edff", to: "/admin/orders" },
                ],
            },

            // ── Update these routes to match your router (used by the Create menu and the command palette) ──
            quickCreate: [
                { label: "New order", icon: "mdi-receipt-text-plus-outline", color: "#0f9d6b", tint: "#dff5ec", to: "/admin/orders" },
                { label: "Add product", icon: "mdi-package-variant-plus", color: "#2f5be7", tint: "#e6edff", to: "/admin/product" },
                { label: "Add brand", icon: "mdi-tag-plus-outline", color: "#d97706", tint: "#fff1d1", to: "/admin/brand" },
                { label: "Add category", icon: "mdi-shape-plus-outline", color: "#7c3aed", tint: "#efe8ff", to: "/admin/category" },
                { label: "Add customer", icon: "mdi-account-plus-outline", color: "#0f766e", tint: "#dcf3f0", to: "/admin/customers" },
            ],
        };
    },

    computed: {
        theme() {
            return this.$store.state.prefs.theme;
        },
        isDark() {
            return this.theme === "dark";
        },
        locale() {
            return this.$store.state.prefs.locale;
        },
        localTz() {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone || "Asia/Dhaka";
            } catch (e) {
                return "Asia/Dhaka";
            }
        },
        tzLabel() {
            try {
                const part = new Intl.DateTimeFormat("en", { timeZoneName: "shortOffset" }).formatToParts(this.clock).find((x) => x.type === "timeZoneName");
                return `${this.localTz.replace(/_/g, " ")}${part ? " · " + part.value : ""}`;
            } catch (e) {
                return "";
            }
        },

        // ── Store and date range (shared by the second bar and the settings drawer) ──
        storeLabel() {
            return (this.stores.find((x) => x.value === this.s.store) || this.stores[0]).label;
        },
        periodRanges() {
            const d = new Date(this.now.getFullYear(), this.now.getMonth(), this.now.getDate());
            const back = (n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() - n);
            return {
                today: [d, d],
                yesterday: [back(1), back(1)],
                "7d": [back(6), d],
                "30d": [back(29), d],
                month: [new Date(d.getFullYear(), d.getMonth(), 1), d],
                year: [new Date(d.getFullYear(), 0, 1), d],
            };
        },
        periodOptions() {
            const fmt = new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "short" });
            return Object.keys(this.periodRanges).map((value) => {
                const [from, to] = this.periodRanges[value];
                return { value, label: PERIOD_LABELS[this.s.dateLang][value], hint: this.sameDay(from, to) ? fmt.format(from) : `${fmt.format(from)} – ${fmt.format(to)}` };
            });
        },
        periodLabel() {
            return PERIOD_LABELS[this.s.dateLang][this.s.period];
        },
    },

    watch: {
        // One deep watcher saves every setting
        s: {
            deep: true,
            handler() {
                this.saveSettings();
            },
        },
        "s.accent"(v) {
            this.applyAccent(v);
        },
        "s.comparePrev"(v) {
            this.$emit("compare-change", v);
        },
        // The site language also drives the clock and calendar language.
        locale: {
            immediate: true,
            handler(v) {
                if (this.s.dateLang !== v) this.s.dateLang = v;
            },
        },
    },

    mounted() {
        this.loadSettings();
        this.s.dateLang = this.locale;
        this.applyAccent(this.s.accent);

        window.addEventListener("keydown", this.onGlobalKey);
        document.addEventListener("fullscreenchange", this.onFullscreenChange);

        this.timers.push(
            setInterval(() => {
                this.clock = new Date();
                if (this.clock.getMinutes() !== this.now.getMinutes()) this.now = this.clock;
            }, 1000)
        );
    },
    beforeUnmount() {
        this.timers.forEach(clearInterval);
        window.removeEventListener("keydown", this.onGlobalKey);
        document.removeEventListener("fullscreenchange", this.onFullscreenChange);
    },

    methods: {
        // ── Small date helpers ──
        isoKey(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
        },
        sameDay(a, b) {
            return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
        },
        fmtTime(d, { seconds = false, tz = "", locale = "" } = {}) {
            const loc = locale || { en: "en-US", bn: "bn-BD", ar: "ar-EG" }[this.s.dateLang];
            const opt = { hour: this.s.hour12 ? "numeric" : "2-digit", minute: "2-digit", hour12: this.s.hour12 };
            if (seconds) opt.second = "2-digit";
            if (tz) opt.timeZone = tz;
            try {
                return new Intl.DateTimeFormat(loc, opt).format(d);
            } catch (e) {
                return d.toLocaleTimeString();
            }
        },
        toast(msg) {
            this.toastText = msg;
            this.toastOpen = true;
        },
        go(to) {
            if (to) this.$router.push(to);
        },

        // ── Settings storage ──
        loadSettings() {
            try {
                // The account's saved header options win over this browser's copy.
                const saved = this.$store.state.auth.user?.preferences?.header;
                const o = saved || JSON.parse(window.localStorage.getItem(SETTINGS_KEY) || "{}");
                const d = this.s;
                Object.keys(d).forEach((k) => {
                    if (!(k in o)) return;
                    if (k === "adjust" || k === "info") d[k] = { ...d[k], ...o[k] };
                    else if (typeof o[k] === typeof d[k]) d[k] = o[k];
                });
                if (!this.languages.some((l) => l.value === d.dateLang)) d.dateLang = "en";
                if (![0, 1, 6].includes(d.weekStart)) d.weekStart = this.weekStart;
                if (![-1, 0, 1].includes(d.hijriShift)) d.hijriShift = this.hijriOffset;
                if (!this.stores.some((x) => x.value === d.store)) d.store = "all";
                if (!PERIOD_LABELS.en[d.period]) d.period = "7d";
                if (!this.prayerCities.some((c) => c.name === d.prayerCity)) d.prayerCity = "Dhaka";
                if (!ACCENTS.some((a) => a.value === d.accent)) d.accent = "green";
            } catch (e) {
                /* ignore broken settings */
            }
        },
        saveSettings() {
            try {
                window.localStorage.setItem(SETTINGS_KEY, JSON.stringify(this.s));
            } catch (e) {
                /* storage unavailable */
            }
            this.$store.dispatch("prefs/savePreferences", { header: this.s });
        },
        resetSettings() {
            Object.assign(this.s, makeDefaults(this.weekStart, this.hijriOffset));
            this.toast("Settings reset");
        },
        applyAccent(name) {
            const a = ACCENTS.find((x) => x.value === name) || ACCENTS[0];
            document.documentElement.style.setProperty("--tt-accent", a.hex);
        },

        // ── Shared setting actions: every button, menu or shortcut calls these ──
        setTheme(name) {
            const next = name === "dark" ? "dark" : "light";
            if (next === this.theme) return;
            this.$store.dispatch("prefs/setTheme", next);
            this.$emit("theme-change", next);
        },
        toggleTheme() {
            this.setTheme(this.isDark ? "light" : "dark");
        },
        setHour12(v) {
            this.s.hour12 = v;
        },
        // Changes the language of the whole site, not just the clock.
        setDateLang(v) {
            this.$store.dispatch("prefs/setLocale", v);
            this.$emit("language-change", v);
        },
        setStore(v) {
            this.s.store = v;
            this.$emit("store-change", v);
        },
        setPeriod(v) {
            this.s.period = v;
            const [from, to] = this.periodRanges[v];
            this.$emit("period-change", { value: v, from: this.isoKey(from), to: this.isoKey(to) });
        },
        resetView() {
            this.setPeriod("7d");
            this.setStore("all");
            this.s.comparePrev = false;
            this.toast("Period and store reset");
        },

        // ── Drawer and palette ──
        openDrawer(tab) {
            if (typeof tab === "string") this.drawerTab = tab;
            this.drawerOpen = true;
        },
        closeDrawer() {
            this.drawerOpen = false;
        },
        openPalette() {
            this.paletteOpen = true;
        },
        closePalette() {
            this.paletteOpen = false;
        },
        // Ctrl / Cmd + K toggles search, Ctrl / Cmd + , toggles the settings sidebar, Esc closes the sidebar
        onGlobalKey(e) {
            if (e.key === "Escape" && this.drawerOpen && !this.paletteOpen) {
                this.closeDrawer();
                return;
            }
            if (!(e.metaKey || e.ctrlKey) || !e.key) return;
            const k = e.key.toLowerCase();
            if (k === "k") {
                e.preventDefault();
                this.paletteOpen = !this.paletteOpen;
            } else if (k === ",") {
                e.preventDefault();
                this.drawerOpen = !this.drawerOpen;
            }
        },

        // ── Share and export (used by section actions and the Data tab) ──
        exportReport(format) {
            const [from, to] = this.periodRanges[this.s.period];
            this.$emit("export", { format, period: this.s.period, from: this.isoKey(from), to: this.isoKey(to), store: this.s.store, compare: this.s.comparePrev });
            this.toast(format === "email" ? "Report will be emailed to you" : `Preparing ${format.toUpperCase()} report…`);
        },
        emailReport() {
            this.exportReport("email");
        },
        printPage() {
            this.drawerOpen = false;
            setTimeout(() => window.print(), 250);
        },
        copyLink() {
            const u = new URL(window.location.href);
            u.searchParams.set("period", this.s.period);
            u.searchParams.set("store", this.s.store);
            const done = () => this.toast("Link copied");
            if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(u.toString()).then(done, done);
            else done();
        },

        // ── Fullscreen ──
        toggleFullscreen() {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
            else document.exitFullscreen && document.exitFullscreen();
        },
        onFullscreenChange() {
            this.isFullscreen = !!document.fullscreenElement;
        },

        // ── Auth ──
        logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            this.$store
                .dispatch("auth/logout")
                .finally(() => {
                    this.loggingOut = false;
                    this.$router.push("/admin/login");
                });
        },
    },
};
</script>
