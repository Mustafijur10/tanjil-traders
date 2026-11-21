<template>
    <header class="tt-shop-header">
        <!-- Announcement bar: live messages from Admin › Content › Announcement Bar -->
        <div v-if="announcements.length" class="tt-announce" role="region" :aria-label="$t('Announcements')">
            <div class="tt-announce-track">
                <span v-for="n in 2" :key="n" class="tt-announce-run" :aria-hidden="n === 2">
                    <component :is="a.link ? 'router-link' : 'span'" v-for="a in announcements" :key="a.id + '-' + n" :to="a.link || undefined" class="tt-announce-item">
                        <v-icon size="15">mdi-bullhorn-outline</v-icon>{{ $t(a.title) }}
                    </component>
                </span>
            </div>
        </div>

        <!-- Main bar -->
        <div class="tt-shop-bar">
            <div class="tt-wrap tt-shop-bar-in">
                <button type="button" class="tt-shop-burger" :aria-label="$t('Open menu')" @click="drawer = true"><v-icon>mdi-menu</v-icon></button>

                <router-link to="/" class="tt-shop-logo" :aria-label="storeName">
                    <img v-if="logo" :src="logo" :alt="storeName" />
                    <template v-else>
                        <span class="tt-sidebar-mark">{{ storeName.charAt(0) }}</span>
                        <span class="tt-shop-logo-text">{{ storeName }}<small v-if="tagline">{{ $t(tagline) }}</small></span>
                    </template>
                </router-link>

                <form class="tt-shop-search" role="search" @submit.prevent="doSearch">
                    <select v-model="scope" class="tt-shop-search-scope" :aria-label="$t('Search in')">
                        <option value="">{{ $t("All") }}</option>
                        <option v-for="c in menu" :key="c.id" :value="c.slug">{{ $t(c.name) }}</option>
                    </select>
                    <input v-model="query" type="search" :placeholder="$t('Search phones, laptops, chargers…')" :aria-label="$t('Search products')" />
                    <button type="submit" :aria-label="$t('Search')"><v-icon size="20">mdi-magnify</v-icon></button>
                </form>

                <div class="tt-shop-actions">
                    <a v-if="phone" :href="`tel:${phone}`" class="tt-shop-hotline">
                        <v-icon size="22">mdi-phone-in-talk-outline</v-icon>
                        <span><small>{{ $t("Hotline") }}</small><b>{{ phone }}</b></span>
                    </a>
                    <PrefToggles />
                    <router-link :to="account.to" class="tt-shop-icon" :aria-label="$t(account.label)">
                        <v-icon size="24">mdi-account-circle-outline</v-icon>
                        <span>{{ $t(account.label) }}</span>
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Category navigation -->
        <nav class="tt-shop-nav" :aria-label="$t('Categories')">
            <div class="tt-wrap tt-shop-nav-in">
                <div class="tt-mega" @mouseleave="mega = false">
                    <button type="button" class="tt-mega-btn" :aria-expanded="mega" @click="mega = !mega" @mouseenter="mega = true">
                        <v-icon size="20">mdi-view-grid-outline</v-icon>{{ $t("All categories") }}<v-icon size="18">mdi-chevron-down</v-icon>
                    </button>
                    <div v-if="mega && menu.length" class="tt-mega-panel">
                        <ul class="tt-mega-list">
                            <li v-for="c in menu" :key="c.id">
                                <router-link :to="`/category/${c.slug}`" :class="{ 'is-on': active?.id === c.id }" @mouseenter="hoverCat = c.id" @click="mega = false">
                                    <v-icon size="18">{{ c.icon || "mdi-shape-outline" }}</v-icon>{{ $t(c.name) }}
                                    <v-icon v-if="c.children.length" size="16" class="ml-auto">mdi-chevron-right</v-icon>
                                </router-link>
                            </li>
                        </ul>
                        <div v-if="active" class="tt-mega-sub">
                            <router-link :to="`/category/${active.slug}`" class="tt-mega-sub-title" @click="mega = false">{{ $t("All {name}", { name: $t(active.name) }) }}<v-icon size="16">mdi-arrow-right</v-icon></router-link>
                            <div class="tt-mega-sub-grid">
                                <router-link v-for="s in active.children" :key="s.id" :to="`/category/${s.slug}`" @click="mega = false">{{ $t(s.name) }}</router-link>
                            </div>
                        </div>
                    </div>
                </div>
                <router-link v-for="c in menu.slice(0, 7)" :key="c.id" :to="`/category/${c.slug}`" class="tt-shop-navlink">{{ $t(c.name) }}</router-link>
                <span class="tt-toolbar-grow"></span>
                <router-link to="/deals" class="tt-shop-navlink is-deal"><v-icon size="17">mdi-lightning-bolt</v-icon>{{ $t("Deals") }}</router-link>
                <router-link to="/contact" class="tt-shop-navlink">{{ $t("Contact") }}</router-link>
            </div>
        </nav>

        <!-- Phone menu -->
        <v-navigation-drawer v-model="drawer" temporary location="left" width="300" class="tt-shop-drawer">
            <div class="tt-shop-drawer-head">
                <b>{{ storeName }}</b>
                <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="drawer = false" />
            </div>
            <v-list density="comfortable" nav>
                <v-list-item to="/" prepend-icon="mdi-home-outline" :title="$t('Home')" @click="drawer = false" />
                <v-list-item to="/deals" prepend-icon="mdi-lightning-bolt" :title="$t('Deals')" @click="drawer = false" />
                <v-list-subheader>{{ $t("Categories") }}</v-list-subheader>
                <template v-for="c in menu" :key="c.id">
                    <v-list-group v-if="c.children.length" :value="c.id">
                        <template #activator="{ props }">
                            <v-list-item v-bind="props" :prepend-icon="c.icon || 'mdi-shape-outline'" :title="$t(c.name)" />
                        </template>
                        <v-list-item :to="`/category/${c.slug}`" :title="$t('All {name}', { name: $t(c.name) })" @click="drawer = false" />
                        <v-list-item v-for="s in c.children" :key="s.id" :to="`/category/${s.slug}`" :title="$t(s.name)" @click="drawer = false" />
                    </v-list-group>
                    <v-list-item v-else :to="`/category/${c.slug}`" :prepend-icon="c.icon || 'mdi-shape-outline'" :title="$t(c.name)" @click="drawer = false" />
                </template>
                <v-list-subheader>{{ $t("Help") }}</v-list-subheader>
                <v-list-item to="/contact" prepend-icon="mdi-message-outline" :title="$t('Contact us')" @click="drawer = false" />
                <v-list-item v-if="phone" :href="`tel:${phone}`" prepend-icon="mdi-phone-outline" :title="phone" />
            </v-list>
        </v-navigation-drawer>
    </header>
</template>

<script>
import { fileUrl } from "@/api";

export default {
    name: "SiteHeader",
    data: () => ({ query: "", scope: "", mega: false, hoverCat: null, drawer: false }),
    computed: {
        settings() {
            return this.$store.state.prefs.settings;
        },
        site() {
            return this.$store.state.prefs.site;
        },
        menu() {
            return this.site.menu;
        },
        announcements() {
            return this.site.announcements;
        },
        storeName() {
            return this.settings["store.name"] || "Tanjil Traders";
        },
        tagline() {
            return this.settings["store.tagline"];
        },
        phone() {
            return this.settings["store.phone"];
        },
        logo() {
            const path = this.$store.getters["prefs/isDark"] ? this.settings["store.logo_dark"] || this.settings["store.logo"] : this.settings["store.logo"];
            return path ? fileUrl(path) : "";
        },
        active() {
            return this.menu.find((c) => c.id === this.hoverCat) || this.menu[0];
        },
        account() {
            const user = this.$store.state.auth.user;
            if (user && ["admin", "staff"].includes(user.role)) return { to: "/admin", label: "Admin" };
            return user ? { to: "/account", label: "Account" } : { to: "/login", label: "Sign in" };
        },
    },
    watch: {
        "$route.query.q": {
            immediate: true,
            handler(q) {
                if (typeof q === "string") this.query = q;
            },
        },
    },
    methods: {
        doSearch() {
            const q = this.query.trim();
            if (!q) return;
            this.$router.push({ path: "/search", query: { q, ...(this.scope && { category: this.scope }) } });
        },
    },
};
</script>
