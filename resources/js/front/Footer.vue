<template>
    <footer class="tt-footer">
        <div class="tt-wrap tt-footer-grid">
            <div class="tt-footer-about">
                <router-link to="/" class="tt-shop-logo is-footer" :aria-label="storeName">
                    <img v-if="logo" :src="logo" :alt="storeName" />
                    <template v-else>
                        <span class="tt-sidebar-mark">{{ storeName.charAt(0) }}</span>
                        <span class="tt-shop-logo-text">{{ storeName }}</span>
                    </template>
                </router-link>
                <p v-if="s['store.footer_text']">{{ $t(s["store.footer_text"]) }}</p>
                <ul class="tt-footer-contact">
                    <li v-if="s['store.phone']"><v-icon size="17">mdi-phone-outline</v-icon><a :href="`tel:${s['store.phone']}`">{{ s["store.phone"] }}</a></li>
                    <li v-if="s['store.email']"><v-icon size="17">mdi-email-outline</v-icon><a :href="`mailto:${s['store.email']}`">{{ s["store.email"] }}</a></li>
                    <li v-if="s['store.address']"><v-icon size="17">mdi-map-marker-outline</v-icon><span>{{ $t(s["store.address"]) }}</span></li>
                    <li v-if="s['store.hours']"><v-icon size="17">mdi-clock-outline</v-icon><span>{{ $t(s["store.hours"]) }}</span></li>
                </ul>
                <div v-if="socials.length" class="tt-footer-social">
                    <a v-for="x in socials" :key="x.label" :href="x.href" target="_blank" rel="noopener" :aria-label="x.label"><v-icon size="19">{{ x.icon }}</v-icon></a>
                </div>
            </div>

            <nav v-if="menu.length" class="tt-footer-col" :aria-label="$t('Shop')">
                <h3>{{ $t("Shop") }}</h3>
                <router-link v-for="c in menu.slice(0, 7)" :key="c.id" :to="`/category/${c.slug}`">{{ $t(c.name) }}</router-link>
                <router-link to="/deals">{{ $t("Deals") }}</router-link>
            </nav>

            <nav class="tt-footer-col" :aria-label="$t('Help')">
                <h3>{{ $t("Help") }}</h3>
                <router-link to="/contact">{{ $t("Contact us") }}</router-link>
                <router-link v-for="p in pages" :key="p.slug" :to="`/${p.slug}`">{{ $t(p.title) }}</router-link>
            </nav>

            <div class="tt-footer-col">
                <h3>{{ $t("We accept") }}</h3>
                <div class="tt-footer-pay">
                    <span v-if="s['checkout.cod_enabled'] !== false"><v-icon size="18">mdi-cash</v-icon>{{ $t("Cash on delivery") }}</span>
                    <span v-if="s['checkout.bkash_enabled']" class="is-bkash">bKash</span>
                    <span v-if="s['checkout.nagad_enabled']" class="is-nagad">Nagad</span>
                </div>
                <div class="tt-footer-trust">
                    <span><v-icon size="18">mdi-shield-check-outline</v-icon>{{ $t("Original products") }}</span>
                    <span><v-icon size="18">mdi-truck-fast-outline</v-icon>{{ $t("Delivery all over Bangladesh") }}</span>
                    <span><v-icon size="18">mdi-autorenew</v-icon>{{ $t("Easy returns") }}</span>
                </div>
            </div>
        </div>

        <div class="tt-footer-bottom">
            <div class="tt-wrap tt-footer-bottom-in">
                <span>© {{ year }} {{ storeName }}. {{ $t("All rights reserved.") }}</span>
                <PrefToggles />
            </div>
        </div>
    </footer>
</template>

<script>
import { fileUrl } from "@/api";

const SOCIAL = [
    { key: "social.facebook", label: "Facebook", icon: "mdi-facebook" },
    { key: "social.instagram", label: "Instagram", icon: "mdi-instagram" },
    { key: "social.youtube", label: "YouTube", icon: "mdi-youtube" },
    { key: "social.tiktok", label: "TikTok", icon: "mdi-music-note" },
    { key: "social.whatsapp", label: "WhatsApp", icon: "mdi-whatsapp" },
];

export default {
    name: "SiteFooter",
    computed: {
        s() {
            return this.$store.state.prefs.settings;
        },
        menu() {
            return this.$store.state.prefs.site.menu;
        },
        pages() {
            return this.$store.state.prefs.site.footer_pages;
        },
        storeName() {
            return this.s["store.name"] || "Tanjil Traders";
        },
        logo() {
            const path = this.s["store.logo_dark"] || this.s["store.logo"];
            return path ? fileUrl(path) : "";
        },
        socials() {
            return SOCIAL.filter((x) => this.s[x.key]).map((x) => ({
                ...x,
                href: x.key === "social.whatsapp" ? `https://wa.me/${String(this.s[x.key]).replace(/\D/g, "")}` : this.s[x.key],
            }));
        },
        year: () => new Date().getFullYear(),
    },
};
</script>
