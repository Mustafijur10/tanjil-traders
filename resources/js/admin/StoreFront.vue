<template>
    <div class="tt-set">
        <!-- ═════════ Hero ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-4">
                <div class="tt-hero-logo"><v-icon size="26" color="white">mdi-storefront-outline</v-icon></div>
                <div>
                    <div class="d-flex align-center ga-3 flex-wrap">
                        <h1 class="tt-h1">{{ settings.store.name }}</h1>
                        <span class="tt-pill" :class="{ off: maintenance }">
                            <span class="tt-pill-dot"></span>{{ maintenance ? "Maintenance" : "Live" }}
                        </span>
                    </div>
                    <div class="tt-hero-sub">
                        <v-icon size="14">mdi-link-variant</v-icon> {{ settings.domain.primary }}
                        <span class="mx-2">·</span><v-icon size="14">mdi-shield-check-outline</v-icon> {{ settings.domain.https ? "SSL Secured" : "SSL off" }}
                        <span class="mx-2">·</span>Uptime 99.98%
                    </div>
                    <div class="tt-hero-sub mt-1">Last saved {{ lastSaved }}</div>
                </div>
                <v-spacer />
                <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="mdi-format-paint" @click="openFeature('theme')">Customize theme</v-btn>
            </div>
        </header>

        <!-- ═════════ Quick stats ═════════ -->
        <div class="tt-stats">
            <div v-for="s in stats" :key="s.label" class="tt-stat">
                <div class="d-flex align-center justify-space-between">
                    <div class="tt-stat-icon" :style="{ background: s.color }"><v-icon size="20" color="white">{{ s.icon }}</v-icon></div>
                    <span class="tt-stat-tag" :style="{ color: s.color }">{{ s.tag }}</span>
                </div>
                <div class="tt-stat-value">{{ s.value }}</div>
                <div class="tt-stat-label">{{ s.label }}</div>
                <div class="tt-stat-bar"><div :style="{ width: s.pct + '%', background: s.color }"></div></div>
            </div>
        </div>

        <!-- ═════════ Store configuration ═════════ -->
        <h2 class="tt-section">Store configuration</h2>
        <div class="tt-grid">
            <button v-for="f in features" :key="f.key" type="button" class="tt-feature" @click="openFeature(f.key)">
                <span class="tt-feature-icon" :style="{ background: f.color }"><v-icon size="22" color="white">{{ f.icon }}</v-icon></span>
                <span class="tt-feature-text">
                    <span class="tt-feature-title">{{ f.title }}</span>
                    <span class="tt-feature-desc">{{ summary(f) }}</span>
                </span>
            </button>
        </div>

        <!-- ═════════ Activity + maintenance ═════════ -->
        <v-row dense class="mt-4">
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card h-100">
                    <div class="tt-card-head"><span class="tt-card-title">Store activity</span></div>
                    <div class="tt-body">
                        <div v-for="(a, i) in activity" :key="i" class="tt-act">
                            <span class="tt-act-icon" :style="{ background: a.color }"><v-icon size="18" color="white">{{ a.icon }}</v-icon></span>
                            <div>
                                <div class="tt-act-text"><b>{{ a.who }}</b> {{ a.text }}</div>
                                <div class="tt-muted tt-small">{{ a.time }}</div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="5">
                <v-card flat class="tt-card h-100">
                    <div class="tt-body">
                        <div class="d-flex align-center ga-3 mb-3">
                            <span class="tt-act-icon" style="background:#c0392b"><v-icon size="20" color="white">mdi-power</v-icon></span>
                            <div>
                                <div class="tt-card-title">Maintenance mode</div>
                                <div class="tt-muted tt-small">Store is currently {{ maintenance ? "offline" : "live" }}</div>
                            </div>
                        </div>
                        <p class="tt-para">Temporarily take your storefront offline while you make changes. Shoppers see a friendly holding page, while admins can still reach the store using the bypass link.</p>
                        <div class="tt-switch-box">
                            <v-switch v-model="maintenance" color="error" hide-details density="compact" label="Enable maintenance mode" @update:model-value="saveMaintenance" />
                        </div>
                        <div class="tt-label mt-4">Admin bypass link</div>
                        <v-text-field :model-value="bypassLink" readonly variant="outlined" density="comfortable" hide-details append-inner-icon="mdi-content-copy" @click:append-inner="copyLink" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Popup form (shared by every feature) ═════════ -->
        <v-dialog v-model="dialog" max-width="560" scrollable>
            <v-card v-if="active" class="tt-dialog">
                <div class="tt-dialog-head">
                    <span class="tt-feature-icon sm" :style="{ background: active.color }"><v-icon size="20" color="white">{{ active.icon }}</v-icon></span>
                    <div>
                        <div class="tt-card-title">{{ active.title }}</div>
                        <div class="tt-muted tt-small">{{ active.desc }}</div>
                    </div>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" size="small" @click="dialog = false" />
                </div>

                <v-card-text class="pt-4">
                    <v-form ref="form" @submit.prevent="save">
                        <div v-for="f in active.fields" :key="f.key" class="mb-3">
                            <v-switch v-if="f.type === 'switch'" v-model="draft[f.key]" :label="f.label" color="success" density="compact" hide-details />

                            <v-textarea v-else-if="f.type === 'textarea'" v-model="draft[f.key]" :label="f.label" :hint="f.hint" rows="3" auto-grow variant="outlined" density="comfortable" :rules="rules(f)" />

                            <v-select v-else-if="f.type === 'select' || f.type === 'multi'" v-model="draft[f.key]" :items="f.items" :label="f.label" :hint="f.hint" :multiple="f.type === 'multi'" :chips="f.type === 'multi'" variant="outlined" density="comfortable" :rules="rules(f)" />

                            <v-text-field v-else v-model="draft[f.key]" :label="f.label" :hint="f.hint" :type="f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : f.type === 'color' ? 'color' : f.type === 'email' ? 'email' : 'text'" variant="outlined" density="comfortable" :rules="rules(f)" />
                        </div>
                    </v-form>
                </v-card-text>

                <v-card-actions class="tt-dialog-foot">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="#0a5548" variant="flat" class="text-none" :loading="saving" @click="save">Save changes</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="toast.show" :color="toast.color" timeout="2600" location="bottom right">{{ toast.text }}</v-snackbar>
    </div>
</template>

<script>
const STORAGE_KEY = "tt_store_settings";

const req = (extra = {}) => ({ required: true, ...extra });

// Every feature = one card + one popup. `fields` drives the popup form.
const FEATURES = [
    { key: "store", title: "Store Settings", desc: "Business info, locations & policies", icon: "mdi-storefront-outline", color: "#167a5e",
      fields: [
        { key: "name", label: "Store name", type: "text", default: "Tanjil Traders", ...req() },
        { key: "tagline", label: "Tagline", type: "text", default: "Electronics & accessories" },
        { key: "email", label: "Contact email", type: "email", default: "support@example.com", ...req() },
        { key: "phone", label: "Phone", type: "text", default: "01711 000 000" },
        { key: "address", label: "Address", type: "textarea", default: "House 32, Road 8/A, Dhanmondi, Dhaka" },
        { key: "returns", label: "Return policy", type: "textarea", default: "7-day return on unopened items." },
      ] },
    { key: "homepage", title: "Homepage Builder", desc: "Drag & drop section editor", icon: "mdi-view-dashboard-outline", color: "#c58a1e",
      fields: [
        { key: "headline", label: "Hero headline", type: "text", default: "Gadgets you can trust", ...req() },
        { key: "subtext", label: "Hero sub-text", type: "textarea", default: "Official warranty on every product." },
        { key: "sections", label: "Sections shown", type: "multi", items: ["Hero slider", "New arrivals", "Best sellers", "Brands", "Categories", "Newsletter"], default: ["Hero slider", "New arrivals", "Best sellers"] },
        { key: "perRow", label: "Products per row", type: "select", items: ["3", "4", "5", "6"], default: "4" },
      ] },
    { key: "theme", title: "Theme Customizer", desc: "Colors, fonts & layout presets", icon: "mdi-format-paint", color: "#2d5f8f",
      fields: [
        { key: "primary", label: "Primary color", type: "color", default: "#0a5548" },
        { key: "accent", label: "Accent color", type: "color", default: "#f59e0b" },
        { key: "font", label: "Font family", type: "select", items: ["Poppins", "Inter", "Roboto", "Plus Jakarta Sans"], default: "Poppins" },
        { key: "layout", label: "Layout", type: "select", items: ["Wide", "Boxed"], default: "Wide" },
        { key: "dark", label: "Enable dark mode toggle", type: "switch", default: false },
      ] },
    { key: "navigation", title: "Navigation Menu", desc: "Header, footer & mega menus", icon: "mdi-menu", color: "#1f8a5b",
      fields: [
        { key: "style", label: "Header style", type: "select", items: ["Classic", "Centered", "Minimal"], default: "Classic" },
        { key: "headerLinks", label: "Header links (comma separated)", type: "text", default: "Home, New arrivals, Best sellers, Brands" },
        { key: "footerLinks", label: "Footer links (comma separated)", type: "text", default: "Privacy, Terms, Returns policy" },
        { key: "sticky", label: "Sticky header", type: "switch", default: true },
        { key: "mega", label: "Enable mega menu", type: "switch", default: true },
      ] },
    { key: "announcement", title: "Announcement Bar", desc: "Site-wide banner messages", icon: "mdi-bullhorn-outline", color: "#7a808e",
      fields: [
        { key: "enabled", label: "Show announcement bar", type: "switch", default: true },
        { key: "message", label: "Message", type: "textarea", default: "Free delivery over ৳5,000 · Same-day in Dhaka", ...req() },
        { key: "start", label: "Start date", type: "date", default: "" },
        { key: "end", label: "End date", type: "date", default: "" },
      ] },
    { key: "banners", title: "Banners & Hero Sliders", desc: "Homepage & campaign creatives", icon: "mdi-image-multiple-outline", color: "#1f8a5b",
      fields: [
        { key: "autoplay", label: "Auto-play slider", type: "switch", default: true },
        { key: "interval", label: "Slide interval (seconds)", type: "number", default: 5 },
        { key: "maxSlides", label: "Maximum slides", type: "number", default: 5 },
        { key: "link", label: "Default banner link", type: "text", default: "/" },
      ] },
    { key: "landing", title: "Landing Pages", desc: "Custom campaign & promo pages", icon: "mdi-file-document-outline", color: "#c58a1e",
      fields: [
        { key: "template", label: "Default template", type: "select", items: ["Promo", "Product launch", "Lead capture"], default: "Promo" },
        { key: "prefix", label: "URL prefix", type: "text", default: "/promo" },
        { key: "pixel", label: "Tracking pixel ID", type: "text", default: "" },
        { key: "ab", label: "Enable A/B testing", type: "switch", default: false },
      ] },
    { key: "seo", title: "SEO Settings", desc: "Meta tags, sitemap & robots.txt", icon: "mdi-text-search", color: "#2d5f8f",
      fields: [
        { key: "title", label: "Meta title", type: "text", default: "Tanjil Traders | Electronics in Bangladesh", ...req() },
        { key: "description", label: "Meta description", type: "textarea", default: "Chargers, cables, audio and more under official warranty." },
        { key: "keywords", label: "Keywords", type: "text", default: "electronics, chargers, earbuds" },
        { key: "robots", label: "robots.txt", type: "textarea", default: "User-agent: *\nAllow: /" },
        { key: "sitemap", label: "Auto-generate sitemap", type: "switch", default: true },
      ] },
    { key: "domain", title: "Domain Settings", desc: "Custom domains & DNS records", icon: "mdi-web", color: "#0f8f4f",
      fields: [
        { key: "primary", label: "Primary domain", type: "text", default: "tanjiltraders.com", ...req() },
        { key: "https", label: "Force HTTPS", type: "switch", default: true },
        { key: "www", label: "Redirect www to root", type: "switch", default: true },
        { key: "dns", label: "DNS notes", type: "textarea", default: "" },
      ] },
    { key: "languages", title: "Store Languages", desc: "Languages & auto-translate", icon: "mdi-translate", color: "#7a808e",
      fields: [
        { key: "default", label: "Default language", type: "select", items: ["English", "বাংলা"], default: "English" },
        { key: "enabled", label: "Enabled languages", type: "multi", items: ["English", "বাংলা", "हिन्दी", "العربية"], default: ["English", "বাংলা"] },
        { key: "auto", label: "Auto-translate product pages", type: "switch", default: false },
      ] },
    { key: "currencies", title: "Currencies", desc: "Default & enabled currencies", icon: "mdi-currency-bdt", color: "#167a5e",
      fields: [
        { key: "default", label: "Default currency", type: "select", items: ["BDT", "USD", "EUR", "INR"], default: "BDT" },
        { key: "enabled", label: "Enabled currencies", type: "multi", items: ["BDT", "USD", "EUR", "INR", "GBP"], default: ["BDT", "USD"] },
        { key: "rounding", label: "Rounding", type: "select", items: ["None", "Nearest 1", "Nearest 5"], default: "None" },
        { key: "autoRates", label: "Update exchange rates daily", type: "switch", default: true },
      ] },
    { key: "tax", title: "Tax Settings", desc: "Regional tax rules & exemptions", icon: "mdi-percent", color: "#c58a1e",
      fields: [
        { key: "name", label: "Tax name", type: "text", default: "VAT" },
        { key: "rate", label: "Rate (%)", type: "number", default: 5 },
        { key: "inclusive", label: "Prices include tax", type: "switch", default: true },
        { key: "exempt", label: "Exemption notes", type: "textarea", default: "" },
      ] },
    { key: "shipping", title: "Shipping Zones", desc: "Zones, rates & carriers", icon: "mdi-truck-delivery-outline", color: "#2d5f8f",
      fields: [
        { key: "freeOver", label: "Free shipping over (৳)", type: "number", default: 5000 },
        { key: "carrier", label: "Default carrier", type: "select", items: ["Pathao", "RedX", "Steadfast", "Sundarban"], default: "Pathao" },
        { key: "handling", label: "Handling time (days)", type: "number", default: 1 },
        { key: "sameDay", label: "Same-day delivery in Dhaka", type: "switch", default: true },
      ] },
    { key: "payment", title: "Payment Gateways", desc: "Methods, keys & test mode", icon: "mdi-credit-card-outline", color: "#1f8a5b",
      fields: [
        { key: "methods", label: "Enabled methods", type: "multi", items: ["bKash", "Nagad", "Card", "Cash on delivery", "Stripe", "PayPal"], default: ["bKash", "Nagad", "Cash on delivery"] },
        { key: "payoutEmail", label: "Payout email", type: "email", default: "" },
        { key: "test", label: "Test mode", type: "switch", default: false },
      ] },
    { key: "checkout", title: "Checkout Customization", desc: "Fields, steps & upsells", icon: "mdi-cart-check", color: "#7a808e",
      fields: [
        { key: "guest", label: "Allow guest checkout", type: "switch", default: true },
        { key: "required", label: "Required fields", type: "multi", items: ["Name", "Phone", "Email", "Address", "Order note"], default: ["Name", "Phone", "Address"] },
        { key: "upsells", label: "Show upsell products", type: "switch", default: true },
      ] },
    { key: "email", title: "Email Templates", desc: "Order, shipping & marketing emails", icon: "mdi-email-outline", color: "#167a5e",
      fields: [
        { key: "sender", label: "Sender name", type: "text", default: "Tanjil Traders", ...req() },
        { key: "from", label: "Sender email", type: "email", default: "orders@example.com", ...req() },
        { key: "template", label: "Template style", type: "select", items: ["Minimal", "Branded", "Plain text"], default: "Branded" },
        { key: "footer", label: "Email footer text", type: "textarea", default: "Thanks for shopping with us." },
      ] },
    { key: "notifications", title: "Notification Settings", desc: "Admin & customer alerts", icon: "mdi-bell-outline", color: "#c58a1e",
      fields: [
        { key: "newOrder", label: "Alert admins on new order", type: "switch", default: true },
        { key: "lowStock", label: "Low-stock alerts", type: "switch", default: true },
        { key: "threshold", label: "Low-stock threshold", type: "number", default: 5 },
        { key: "shipUpdates", label: "Send customers shipping updates", type: "switch", default: true },
      ] },
    { key: "media", title: "Media Library", desc: "Uploads, formats & optimization", icon: "mdi-folder-multiple-image", color: "#2d5f8f",
      fields: [
        { key: "maxMb", label: "Max upload size (MB)", type: "number", default: 5 },
        { key: "types", label: "Allowed types", type: "multi", items: ["JPG", "PNG", "WEBP", "SVG", "MP4"], default: ["JPG", "PNG", "WEBP"] },
        { key: "optimize", label: "Auto-optimize images", type: "switch", default: true },
      ] },
];

// One-line summaries shown on each card (updates after saving)
const SUMMARY = {
    store: (s) => `${s.email} · ${s.phone}`,
    homepage: (s) => `${s.sections.length} sections · ${s.perRow} per row`,
    theme: (s) => `${s.font} · ${s.layout} layout`,
    navigation: (s) => `${s.style} header · ${s.mega ? "mega menu on" : "mega menu off"}`,
    announcement: (s) => (s.enabled ? "Bar is showing" : "Bar is hidden"),
    banners: (s) => `${s.maxSlides} slides · every ${s.interval}s`,
    landing: (s) => `${s.template} template · ${s.prefix}`,
    seo: (s) => (s.sitemap ? "Sitemap auto-generated" : "Sitemap off"),
    domain: (s) => `${s.primary}${s.https ? " · HTTPS" : ""}`,
    languages: (s) => `${s.enabled.length} languages · ${s.auto ? "auto-translate" : "manual"}`,
    currencies: (s) => `${s.default} default · ${s.enabled.length} enabled`,
    tax: (s) => `${s.name} ${s.rate}% · ${s.inclusive ? "included" : "added"}`,
    shipping: (s) => `Free over ৳${Number(s.freeOver).toLocaleString("en-IN")} · ${s.carrier}`,
    payment: (s) => `${s.methods.slice(0, 2).join(", ")}${s.methods.length > 2 ? ` & ${s.methods.length - 2} more` : ""}`,
    checkout: (s) => `${s.guest ? "Guest checkout on" : "Login required"} · ${s.required.length} required`,
    email: (s) => `${s.template} · ${s.from}`,
    notifications: (s) => `${[s.newOrder, s.lowStock, s.shipUpdates].filter(Boolean).length} alerts on`,
    media: (s) => `Max ${s.maxMb} MB · ${s.types.join(", ")}`,
};

const defaults = () => {
    const out = {};
    FEATURES.forEach((f) => {
        out[f.key] = {};
        f.fields.forEach((x) => (out[f.key][x.key] = Array.isArray(x.default) ? [...x.default] : x.default));
    });
    return out;
};

export default {
    name: "StoreSettings",

    data() {
        return {
            features: FEATURES,
            settings: defaults(),
            maintenance: false,
            dialog: false,
            saving: false,
            activeKey: null,
            draft: {},
            lastSaved: "never",
            toast: { show: false, text: "", color: "success" },
            stats: [
                { label: "Store visits today", value: "8,420", tag: "↗ 6.4%", icon: "mdi-account-group-outline", color: "#167a5e", pct: 62 },
                { label: "Conversion rate", value: "4.82%", tag: "↗ 0.3%", icon: "mdi-target", color: "#167a5e", pct: 48 },
                { label: "Page speed score", value: "92/100", tag: "↗ 4pts", icon: "mdi-speedometer", color: "#2d5f8f", pct: 92 },
                { label: "Orders today", value: "156", tag: "↗ 12.1%", icon: "mdi-shopping-outline", color: "#c58a1e", pct: 70 },
                { label: "Active theme", value: "Dream Admin", tag: "v3.2", icon: "mdi-palette-outline", color: "#c58a1e", pct: 100 },
            ],
            activity: [
                { icon: "mdi-format-paint", color: "#1f8a5b", who: "Amelia Hart", text: "published theme changes to Homepage Builder", time: "12 minutes ago" },
                { icon: "mdi-bullhorn-outline", color: "#2d5f8f", who: "Announcement Bar", text: "campaign scheduled for Jul 25", time: "1 hour ago" },
                { icon: "mdi-credit-card-outline", color: "#c58a1e", who: "bKash", text: "was enabled as a payment method", time: "3 hours ago" },
                { icon: "mdi-web", color: "#167a5e", who: "Domain", text: "SSL certificate renewed", time: "Yesterday, 2:15 PM" },
            ],
        };
    },

    computed: {
        active() {
            return this.features.find((f) => f.key === this.activeKey) || null;
        },
        bypassLink() {
            return `https://${this.settings.domain.primary}/?bypass=${btoa(this.settings.domain.primary).slice(0, 10)}`;
        },
    },

    created() {
        // Load saved values. Swap for: axios.get("/api/admin/settings")
        try {
            const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
            if (raw) {
                Object.keys(this.settings).forEach((k) => Object.assign(this.settings[k], raw.settings?.[k] || {}));
                this.maintenance = !!raw.maintenance;
                this.lastSaved = raw.lastSaved || "never";
            }
        } catch (e) {
            /* ignore corrupt storage */
        }
    },

    methods: {
        summary(f) {
            return SUMMARY[f.key] ? SUMMARY[f.key](this.settings[f.key]) : f.desc;
        },
        rules(f) {
            return f.required ? [(v) => (Array.isArray(v) ? v.length > 0 : !!String(v ?? "").trim()) || `${f.label} is required`] : [];
        },
        openFeature(key) {
            this.activeKey = key;
            this.draft = JSON.parse(JSON.stringify(this.settings[key]));
            this.dialog = true;
        },
        async save() {
            const { valid } = await this.$refs.form.validate();
            if (!valid) return;
            this.saving = true;
            try {
                // Swap for: await axios.put(`/api/admin/settings/${this.activeKey}`, this.draft);
                this.settings[this.activeKey] = { ...this.draft };
                this.persist();
                this.logActivity(this.active.icon, this.active.color, "You", `updated ${this.active.title}`);
                this.dialog = false;
                this.notify(`${this.active.title} saved`);
            } catch (e) {
                this.notify("Could not save. Please try again.", "error");
            } finally {
                this.saving = false;
            }
        },
        saveMaintenance(val) {
            this.persist();
            this.logActivity("mdi-power", "#c0392b", "You", val ? "turned maintenance mode on" : "turned maintenance mode off");
            this.notify(val ? "Store is now in maintenance mode" : "Store is live again", val ? "warning" : "success");
        },
        persist() {
            this.lastSaved = new Date().toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ settings: this.settings, maintenance: this.maintenance, lastSaved: this.lastSaved }));
        },
        logActivity(icon, color, who, text) {
            this.activity.unshift({ icon, color, who, text, time: "Just now" });
            this.activity = this.activity.slice(0, 6);
        },
        copyLink() {
            navigator.clipboard?.writeText(this.bypassLink);
            this.notify("Bypass link copied");
        },
        notify(text, color = "success") {
            this.toast = { show: true, text, color };
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-set {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
}

/* Hero */
.tt-hero { background: linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%); color: #fff; border-radius: 14px 14px 0 0; padding: 24px; }
.tt-hero-logo { width: 62px; height: 62px; border-radius: 12px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); }
.tt-h1 { font-size: 24px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.75); margin-top: 4px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; color: #6ee7b7; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-pill.off { color: #fcd34d; }
.tt-pill.off .tt-pill-dot { background: #fcd34d; box-shadow: 0 0 0 3px rgba(252, 211, 77, 0.25); }
.tt-add { color: #0a5548 !important; font-weight: 600; }

/* Stats strip */
.tt-stats { display: grid; grid-template-columns: repeat(5, 1fr); background: #fff; border: 1px solid var(--line); border-top: 0; border-radius: 0 0 14px 14px; overflow: hidden; }
.tt-stat { padding: 18px 20px; border-left: 1px solid var(--line); }
.tt-stat:first-child { border-left: 0; }
.tt-stat-icon { width: 38px; height: 38px; border-radius: 8px; display: grid; place-items: center; }
.tt-stat-tag { font-size: 12px; font-weight: 600; }
.tt-stat-value { font-size: 20px; font-weight: 700; margin-top: 12px; letter-spacing: -0.3px; }
.tt-stat-label { font-size: 12.5px; color: var(--muted); }
.tt-stat-bar { height: 4px; border-radius: 4px; background: #eef2f1; margin-top: 12px; overflow: hidden; }
.tt-stat-bar div { height: 100%; border-radius: 4px; }
@media (max-width: 1100px) { .tt-stats { grid-template-columns: repeat(2, 1fr); } .tt-stat { border-top: 1px solid var(--line); } }

/* Section + feature cards */
.tt-section { font-size: 18px; font-weight: 600; margin: 26px 0 12px; }
.tt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
@media (max-width: 1100px) { .tt-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .tt-grid { grid-template-columns: 1fr; } }

.tt-feature {
    display: flex; align-items: center; gap: 16px; width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer;
    background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 20px;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04); transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.tt-feature:hover { border-color: #0f9d6b; box-shadow: 0 4px 14px rgba(15, 42, 38, 0.08); }
.tt-feature:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-feature-icon { width: 46px; height: 46px; border-radius: 10px; display: grid; place-items: center; flex: none; transform: rotate(-6deg); box-shadow: 0 4px 10px rgba(15, 42, 38, 0.18); }
.tt-feature-icon.sm { width: 38px; height: 38px; transform: none; }
.tt-feature-text { display: flex; flex-direction: column; min-width: 0; }
.tt-feature-title { font-size: 15px; font-weight: 600; }
.tt-feature-desc { font-size: 13px; color: var(--muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Cards (activity / maintenance) */
.tt-card { background: #fff; border: 1px solid var(--line); border-radius: 10px !important; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04); }
.tt-card-head { padding: 16px 24px 0; }
.tt-card-title { font-size: 16px; font-weight: 600; }
.tt-body { padding: 20px 24px; }
.tt-muted { color: var(--muted); }
.tt-small { font-size: 12.5px; }
.tt-para { font-size: 13px; line-height: 1.6; color: var(--muted); margin-bottom: 14px; }
.tt-label { font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: #3d5450; margin-bottom: 6px; }
.tt-switch-box { background: #f1f4f3; border-radius: 8px; padding: 2px 14px; }
.tt-act { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px; }
.tt-act:last-child { margin-bottom: 0; }
.tt-act-icon { width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-act-text { font-size: 14px; }

/* Popup */
.tt-dialog { border-radius: 12px !important; }
.tt-dialog-head { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--line); background: linear-gradient(90deg, #e3f6ee, #fff); }
.tt-dialog-foot { padding: 12px 20px; border-top: 1px solid var(--line); }
</style>