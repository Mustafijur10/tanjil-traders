<template>
    <section class="tt-box tt-preview-box">
        <div class="tt-box-head">
            <span class="tt-box-ico"><v-icon size="18">{{ meta.icon }}</v-icon></span>
            <div>
                <h2>{{ $t(meta.title) }}</h2>
                <p>{{ $t("Updates as you type. Save to make it live.") }}</p>
            </div>
        </div>

        <div class="tt-box-body">
            <!-- Store information: the contact card customers see in the footer and on invoices -->
            <div v-if="section === 'store'" class="tt-pv-store">
                <div class="tt-pv-brand">
                    <img v-if="v['store.logo']" :src="url(v['store.logo'])" alt="" />
                    <span v-else class="tt-sidebar-mark">{{ (v["store.name"] || "T").charAt(0) }}</span>
                    <div>
                        <b>{{ v["store.name"] || $t("Store name") }}</b>
                        <small>{{ v["store.tagline"] || $t("Tagline") }}</small>
                    </div>
                </div>
                <p v-if="v['store.footer_text']" class="tt-pv-about">{{ v["store.footer_text"] }}</p>
                <ul class="tt-pv-contact">
                    <li v-for="row in contactRows" :key="row.icon" :class="{ 'is-empty': !row.value }">
                        <v-icon size="16">{{ row.icon }}</v-icon><span>{{ row.value || $t(row.empty) }}</span>
                    </li>
                </ul>
                <div class="tt-pv-social">
                    <span v-for="s in socials" :key="s.key" :class="{ 'is-off': !v[s.key] }" :title="s.label"><v-icon size="17">{{ s.icon }}</v-icon></span>
                </div>
                <p class="tt-muted tt-small mt-3">{{ $t("Empty items are hidden on the website.") }}</p>
            </div>

            <!-- Appearance: a small sample of the shop in the chosen theme and colour -->
            <div v-else-if="section === 'appearance'">
                <div class="tt-pv-site" :class="v['appearance.default_theme'] === 'dark' ? 'is-dark' : ''" :style="{ '--pv-accent': accentHex }">
                    <div class="tt-pv-site-bar">
                        <span class="tt-pv-dot"></span>
                        <b>{{ $store.state.prefs.settings["store.name"] || "Tanjil Traders" }}</b>
                        <span class="tt-pv-search"></span>
                        <span class="tt-pv-pill">{{ v["language.default"] === "bn" ? "বাংলা" : "English" }}</span>
                    </div>
                    <div class="tt-pv-site-body">
                        <div class="tt-pv-card" v-for="n in 2" :key="n">
                            <span class="tt-pv-img"></span>
                            <span class="tt-pv-line"></span>
                            <span class="tt-pv-line is-short"></span>
                            <span class="tt-pv-price">৳24,500</span>
                            <span class="tt-pv-btn">{{ v["language.default"] === "bn" ? "কার্টে যোগ করুন" : "Add to cart" }}</span>
                        </div>
                    </div>
                </div>
                <ul class="tt-pv-contact mt-4">
                    <li><v-icon size="16">mdi-theme-light-dark</v-icon><span>{{ $t(v["appearance.allow_theme_switch"] ? "Visitors can switch light and dark" : "Theme is fixed for visitors") }}</span></li>
                    <li><v-icon size="16">mdi-translate</v-icon><span>{{ $t(v["language.allow_switch"] ? "Visitors can switch English and Bangla" : "Language is fixed for visitors") }}</span></li>
                </ul>
            </div>

            <!-- Payments: the payment step of checkout -->
            <div v-else-if="section === 'payments'" class="tt-pv-pay">
                <div class="tt-pv-label">{{ $t("Payment method") }}</div>
                <div v-for="(m, i) in payMethods" :key="m.key" class="tt-pv-option" :class="{ 'is-on': i === 0 }">
                    <span class="tt-pv-radio"></span>
                    <v-icon size="18">{{ m.icon }}</v-icon>
                    <span>
                        <b>{{ $t(m.title) }}</b>
                        <small v-if="m.hint">{{ m.hint }}</small>
                    </span>
                </div>
                <EmptyState v-if="!payMethods.length" icon="mdi-cash-remove" title="No payment method is on" text="Customers cannot place orders until at least one is switched on." />
                <p class="tt-muted tt-small mt-3">
                    {{ $t(v["checkout.guest_checkout"] ? "Customers can order without creating an account." : "Customers must sign in before ordering.") }}
                </p>
            </div>

            <!-- Integrations & SEO: the home page in Google -->
            <GooglePreview v-else-if="section === 'integrations'" :title="v['seo.meta_title']" :description="v['seo.meta_description']" path="/" />
        </div>
    </section>
</template>

<script>
/* Right-hand live preview for the simple settings pages. `values` is the unsaved form. */
import { fileUrl } from "@/api";
import { ACCENTS } from "./schema";
import GooglePreview from "@/components/ui/GooglePreview.vue";

const META = {
    store: { title: "How customers see your store", icon: "mdi-storefront-outline" },
    appearance: { title: "Website preview", icon: "mdi-palette-outline" },
    payments: { title: "Checkout preview", icon: "mdi-credit-card-outline" },
    integrations: { title: "Your home page in Google", icon: "mdi-google" },
};

export default {
    name: "SettingsPreview",
    components: { GooglePreview },
    props: {
        section: { type: String, required: true },
        values: { type: Object, required: true },
    },
    computed: {
        v() {
            return this.values;
        },
        meta() {
            return META[this.section] || META.store;
        },
        contactRows() {
            return [
                { icon: "mdi-phone-outline", value: this.v["store.phone"], empty: "Phone" },
                { icon: "mdi-email-outline", value: this.v["store.email"], empty: "Email" },
                { icon: "mdi-map-marker-outline", value: this.v["store.address"], empty: "Address" },
                { icon: "mdi-clock-outline", value: this.v["store.hours"], empty: "Opening hours" },
            ];
        },
        socials: () => [
            { key: "social.facebook", icon: "mdi-facebook", label: "Facebook" },
            { key: "social.instagram", icon: "mdi-instagram", label: "Instagram" },
            { key: "social.youtube", icon: "mdi-youtube", label: "YouTube" },
            { key: "social.tiktok", icon: "mdi-music-note", label: "TikTok" },
            { key: "social.whatsapp", icon: "mdi-whatsapp", label: "WhatsApp" },
        ],
        accentHex() {
            return (ACCENTS.find((a) => a.value === this.v["appearance.accent"]) || ACCENTS[0]).hex;
        },
        payMethods() {
            return [
                this.v["checkout.cod_enabled"] && { key: "cod", title: "Cash on delivery", icon: "mdi-cash", hint: "" },
                this.v["checkout.bkash_enabled"] && { key: "bkash", title: "bKash", icon: "mdi-cellphone-arrow-down", hint: this.v["checkout.bkash_number"] || this.$t("Add your bKash number") },
                this.v["checkout.nagad_enabled"] && { key: "nagad", title: "Nagad", icon: "mdi-cellphone-arrow-down", hint: this.v["checkout.nagad_number"] || this.$t("Add your Nagad number") },
            ].filter(Boolean);
        },
    },
    methods: {
        url: fileUrl,
    },
};
</script>
