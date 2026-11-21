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
                    <input
                        v-model="query"
                        type="search"
                        autocomplete="off"
                        :placeholder="$t('Search phones, laptops, chargers…')"
                        :aria-label="$t('Search products')"
                        aria-autocomplete="list"
                        :aria-expanded="showSuggest"
                        @focus="focused = true"
                        @blur="onBlur"
                        @keydown.esc="focused = false"
                        @keydown.down.prevent="move(1)"
                        @keydown.up.prevent="move(-1)"
                        @keydown.enter="activeLink && (($event.preventDefault()), openLink(activeLink))"
                    />
                    <button type="submit" :aria-label="$t('Search')"><v-icon size="20">mdi-magnify</v-icon></button>

                    <!-- Live suggestions -->
                    <div v-if="showSuggest" class="tt-suggest" role="listbox">
                        <template v-if="suggest.products.length">
                            <div class="tt-suggest-label">{{ $t("Products") }}</div>
                            <router-link
                                v-for="(p, i) in suggest.products"
                                :key="p.id"
                                :to="`/product/${p.slug}`"
                                class="tt-suggest-product"
                                :class="{ 'is-active': active === i }"
                                role="option"
                                @mousedown.prevent="openLink(`/product/${p.slug}`)"
                            >
                                <span class="tt-thumb"><img v-if="p.image" :src="fileUrl(p.image)" alt="" /><v-icon v-else size="18">mdi-image-outline</v-icon></span>
                                <span class="tt-ellipsis">{{ p.name }}</span>
                                <b>{{ $money(p.final_price) }}</b>
                            </router-link>
                        </template>
                        <div v-if="suggest.categories.length || suggest.brands.length" class="tt-suggest-tags">
                            <router-link v-for="c in suggest.categories" :key="'c' + c.slug" :to="`/category/${c.slug}`" class="tt-subcat" @mousedown.prevent="openLink(`/category/${c.slug}`)">
                                <v-icon size="15">mdi-shape-outline</v-icon>{{ $t(c.name) }}
                            </router-link>
                            <router-link v-for="b in suggest.brands" :key="'b' + b.slug" :to="`/brand/${b.slug}`" class="tt-subcat" @mousedown.prevent="openLink(`/brand/${b.slug}`)">
                                <v-icon size="15">mdi-tag-outline</v-icon>{{ b.name }}
                            </router-link>
                        </div>
                        <button type="submit" class="tt-suggest-all" @mousedown.prevent="doSearch">{{ $t("See all results for “{q}”", { q: query.trim() }) }}<v-icon size="16">mdi-arrow-right</v-icon></button>
                    </div>
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
                    <router-link to="/wishlist" class="tt-shop-icon is-wish" :aria-label="$t('Wishlist')">
                        <v-icon size="24">mdi-heart-outline</v-icon>
                        <em v-if="wishCount" class="tt-shop-badge">{{ $num(wishCount) }}</em>
                        <span>{{ $t("Wishlist") }}</span>
                    </router-link>
                    <button type="button" class="tt-shop-icon" :aria-label="$t('Cart, {n} items', { n: cartCount })" @click="$store.commit('cart/drawer', true)">
                        <v-icon size="24">mdi-cart-outline</v-icon>
                        <em v-if="cartCount" class="tt-shop-badge">{{ $num(cartCount) }}</em>
                        <span>{{ $t("Cart") }}</span>
                    </button>
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
                                <router-link :to="`/category/${c.slug}`" :class="{ 'is-on': activeCategory?.id === c.id }" @mouseenter="hoverCat = c.id" @click="mega = false">
                                    <v-icon size="18">{{ c.icon || "mdi-shape-outline" }}</v-icon>{{ $t(c.name) }}
                                    <v-icon v-if="c.children.length" size="16" class="ml-auto">mdi-chevron-right</v-icon>
                                </router-link>
                            </li>
                        </ul>
                        <div v-if="activeCategory" class="tt-mega-sub">
                            <router-link :to="`/category/${activeCategory.slug}`" class="tt-mega-sub-title" @click="mega = false">{{ $t("All {name}", { name: $t(activeCategory.name) }) }}<v-icon size="16">mdi-arrow-right</v-icon></router-link>
                            <div class="tt-mega-sub-grid">
                                <router-link v-for="s in activeCategory.children" :key="s.id" :to="`/category/${s.slug}`" @click="mega = false">{{ $t(s.name) }}</router-link>
                            </div>
                        </div>
                    </div>
                </div>
                <router-link v-for="c in menu.slice(0, 7)" :key="c.id" :to="`/category/${c.slug}`" class="tt-shop-navlink">{{ $t(c.name) }}</router-link>
                <span class="tt-toolbar-grow"></span>
                <router-link to="/deals" class="tt-shop-navlink is-deal"><v-icon size="17">mdi-lightning-bolt</v-icon>{{ $t("Deals") }}</router-link>
                <router-link to="/track-order" class="tt-shop-navlink">{{ $t("Track order") }}</router-link>
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
                <v-list-item to="/track-order" prepend-icon="mdi-map-marker-path" :title="$t('Track your order')" @click="drawer = false" />
                <v-list-item to="/wishlist" prepend-icon="mdi-heart-outline" :title="$t('Wishlist')" @click="drawer = false" />
                <v-list-item to="/contact" prepend-icon="mdi-message-outline" :title="$t('Contact us')" @click="drawer = false" />
                <v-list-item v-if="phone" :href="`tel:${phone}`" prepend-icon="mdi-phone-outline" :title="phone" />
            </v-list>
        </v-navigation-drawer>
    </header>
</template>

<script>
import api, { fileUrl } from "@/api";

export default {
    name: "SiteHeader",
    data: () => ({
        query: "",
        scope: "",
        mega: false,
        hoverCat: null,
        drawer: false,
        focused: false,
        suggest: { products: [], categories: [], brands: [] },
        active: -1,
        suggestTimer: null,
    }),
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
        activeCategory() {
            return this.menu.find((c) => c.id === this.hoverCat) || this.menu[0];
        },
        showSuggest() {
            const s = this.suggest;
            return this.focused && this.query.trim().length >= 2 && (s.products.length || s.categories.length || s.brands.length);
        },
        activeLink() {
            const p = this.suggest.products[this.active];
            return this.showSuggest && p ? `/product/${p.slug}` : null;
        },
        account() {
            const user = this.$store.state.auth.user;
            if (user && ["admin", "staff"].includes(user.role)) return { to: "/admin", label: "Admin" };
            return user ? { to: "/account", label: "Account" } : { to: "/login", label: "Sign in" };
        },
        cartCount() {
            return this.$store.getters["cart/count"];
        },
        wishCount() {
            return this.$store.getters["wishlist/count"];
        },
    },
    watch: {
        query(q) {
            clearTimeout(this.suggestTimer);
            this.active = -1;
            if (q.trim().length < 2) {
                this.suggest = { products: [], categories: [], brands: [] };
                return;
            }
            this.suggestTimer = setTimeout(async () => {
                try {
                    this.suggest = (await api.get("/search/suggest", { params: { q: q.trim() }, quiet: true })).data;
                } catch (e) {
                    /* suggestions are optional */
                }
            }, 220);
        },
        "$route.fullPath"() {
            this.focused = false;
        },
        "$route.query.q": {
            immediate: true,
            handler(q) {
                if (typeof q === "string") this.query = q;
            },
        },
    },
    methods: {
        fileUrl,
        doSearch() {
            const q = this.query.trim();
            if (!q) return;
            this.focused = false;
            // Searching inside one category goes to that category page with the words as a filter
            if (this.scope) this.$router.push({ path: `/category/${this.scope}`, query: { q } });
            else this.$router.push({ path: "/search", query: { q } });
        },
        onBlur() {
            setTimeout(() => (this.focused = false), 150);
        },
        move(dir) {
            const n = this.suggest.products.length;
            if (!n) return;
            this.active = (this.active + dir + n) % n;
        },
        openLink(to) {
            this.focused = false;
            this.$router.push(to);
        },
    },
};
</script>
