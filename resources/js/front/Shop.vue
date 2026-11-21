<template>
    <div class="tt-wrap tt-shop">
        <!-- Breadcrumb and heading -->
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link>
            <template v-for="c in crumbs" :key="c.to || c.label">
                <v-icon size="14">mdi-chevron-right</v-icon>
                <router-link v-if="c.to" :to="c.to">{{ c.label }}</router-link>
                <span v-else>{{ c.label }}</span>
            </template>
        </nav>

        <header class="tt-shop-head">
            <img v-if="brand?.logo" :src="url(brand.logo)" :alt="brand.name" class="tt-shop-head-logo" />
            <div class="tt-shop-head-text">
                <h1 class="tt-doc-title">{{ heading }}</h1>
                <p v-if="!loading || total" class="tt-muted">{{ $tn(total, "{n} product", "{n} products") }}</p>
            </div>
        </header>
        <p v-if="intro" class="tt-shop-intro">{{ intro }}</p>

        <div v-if="facets.subcategories?.length" class="tt-subcats" role="list">
            <router-link v-for="c in facets.subcategories" :key="c.slug" :to="`/category/${c.slug}`" class="tt-subcat" role="listitem">
                {{ $t(c.name) }} <small>{{ $num(c.count) }}</small>
            </router-link>
        </div>

        <div class="tt-shop-layout">
            <!-- Desktop filters -->
            <aside class="tt-shop-side" :aria-label="$t('Filters')">
                <div class="tt-shop-side-head">
                    <b><v-icon size="18">mdi-tune-variant</v-icon> {{ $t("Filters") }}</b>
                    <button v-if="activeChips.length" type="button" class="tt-flink" @click="clearAll">{{ $t("Clear all") }}</button>
                </div>
                <FilterPanel v-if="facetsReady" :facets="facets" :query="filterQuery" :hide-brand="mode === 'brand'" :hide-sale="mode === 'deals'" @update="go" />
            </aside>

            <main class="tt-shop-main">
                <!-- Toolbar -->
                <div class="tt-shop-toolbar">
                    <v-btn-secondary class="tt-shop-filter-btn" prepend-icon="mdi-tune-variant" @click="drawer = true">
                        {{ $t("Filters") }}<span v-if="activeChips.length" class="tt-count ml-2">{{ $num(activeChips.length) }}</span>
                    </v-btn-secondary>
                    <div class="tt-toolbar-grow"></div>
                    <label class="tt-shop-sort">
                        <span class="tt-muted tt-small">{{ $t("Sort by") }}</span>
                        <select :value="route.query.sort || 'featured'" :aria-label="$t('Sort by')" @change="go({ ...route.query, sort: $event.target.value === 'featured' ? undefined : $event.target.value, page: undefined })">
                            <option v-for="s in sorts" :key="s.value" :value="s.value">{{ $t(s.label) }}</option>
                        </select>
                    </label>
                </div>

                <!-- Active filters -->
                <div v-if="activeChips.length" class="tt-active-filters">
                    <button v-for="c in activeChips" :key="c.key + c.value" type="button" class="tt-fchip" :aria-label="$t('Remove filter {name}', { name: c.label })" @click="removeChip(c)">
                        {{ c.label }}<v-icon size="15">mdi-close</v-icon>
                    </button>
                    <button type="button" class="tt-flink" @click="clearAll">{{ $t("Clear all") }}</button>
                </div>

                <!-- Products -->
                <div v-if="loading && !products.length" class="tt-pgrid">
                    <span v-for="n in 8" :key="n" class="tt-skel tt-home-skel-card"></span>
                </div>

                <EmptyState
                    v-else-if="!products.length"
                    icon="mdi-magnify-close"
                    :title="mode === 'search' ? 'No products found' : 'Nothing matches these filters'"
                    :text="mode === 'search' ? 'Check the spelling or try a shorter word, like the brand or model.' : 'Try removing a filter or two.'"
                >
                    <v-btn-primary v-if="activeChips.length" @click="clearAll">{{ $t("Clear all filters") }}</v-btn-primary>
                    <v-btn-secondary v-else to="/shop">{{ $t("See all products") }}</v-btn-secondary>
                </EmptyState>

                <div v-else class="tt-pgrid" :class="{ 'is-loading': loading }">
                    <ProductCard v-for="p in products" :key="p.id" :p="p" />
                </div>

                <div v-if="lastPage > 1" class="tt-shop-pages">
                    <v-pagination :model-value="page" :length="lastPage" total-visible="7" density="comfortable" active-color="primary" @update:model-value="goPage" />
                </div>
            </main>
        </div>

        <!-- Phone and tablet filters -->
        <v-navigation-drawer v-model="drawer" temporary location="right" width="340" class="tt-filter-drawer">
            <div class="tt-filter-drawer-head">
                <b>{{ $t("Filters") }}</b>
                <button v-if="activeChips.length" type="button" class="tt-flink" @click="clearAll">{{ $t("Clear all") }}</button>
                <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="drawer = false" />
            </div>
            <div class="tt-filter-drawer-body">
                <FilterPanel v-if="facetsReady" :facets="facets" :query="filterQuery" :hide-brand="mode === 'brand'" :hide-sale="mode === 'deals'" @update="go" />
            </div>
            <div class="tt-filter-drawer-foot">
                <v-btn-primary block size="large" :loading="loading" @click="drawer = false">{{ $tn(total, "Show {n} product", "Show {n} products") }}</v-btn-primary>
            </div>
        </v-navigation-drawer>
    </div>
</template>

<script>
/*
 * One page for /shop, /category/:slug, /brand/:slug, /search?q= and /deals.
 * Every filter lives in the URL, so filtered pages can be shared, bookmarked and use the back button.
 */
import api, { fileUrl } from "@/api";
import ProductCard from "@/front/components/ProductCard.vue";
import FilterPanel from "@/front/components/FilterPanel.vue";

// URL keys that are not spec filters
const RESERVED = ["q", "brand", "min", "max", "stock", "sale", "rating", "sort", "page", "category"];
const SORTS = [
    { value: "featured", label: "Recommended" },
    { value: "newest", label: "Newest" },
    { value: "price_asc", label: "Price: low to high" },
    { value: "price_desc", label: "Price: high to low" },
    { value: "popular", label: "Best selling" },
    { value: "rating", label: "Top rated" },
];

export default {
    name: "Shop",
    components: { ProductCard, FilterPanel },
    data: () => ({
        loading: true,
        products: [],
        total: 0,
        page: 1,
        lastPage: 1,
        facets: {},
        facetsReady: false,
        category: null,
        brand: null,
        drawer: false,
        requestId: 0,
    }),
    computed: {
        route() {
            return this.$route;
        },
        sorts: () => SORTS,
        mode() {
            const p = this.$route.path;
            if (p.startsWith("/category/")) return "category";
            if (p.startsWith("/brand/")) return "brand";
            if (p === "/search") return "search";
            if (p === "/deals") return "deals";
            return "shop";
        },
        // Only the filter keys (sort and page are handled by the toolbar and pager)
        filterQuery() {
            const { sort, ...rest } = this.$route.query;
            return rest;
        },
        heading() {
            if (this.mode === "category") return this.category ? this.$t(this.category.name) : "";
            if (this.mode === "brand") return this.brand?.name || "";
            if (this.mode === "search") return this.$t("Results for “{q}”", { q: this.$route.query.q || "" });
            if (this.mode === "deals") return this.$t("Deals and offers");
            return this.$t("All products");
        },
        intro() {
            if (this.mode === "category") return this.category?.description ? this.$t(this.category.description) : "";
            if (this.mode === "brand") return this.brand?.description || "";
            return "";
        },
        crumbs() {
            if (this.mode === "category" && this.category) {
                const list = [{ label: this.$t("Shop"), to: "/shop" }];
                if (this.category.parent) list.push({ label: this.$t(this.category.parent.name), to: `/category/${this.category.parent.slug}` });
                list.push({ label: this.$t(this.category.name) });
                return list;
            }
            if (this.mode === "brand") return [{ label: this.$t("Brands"), to: "/brands" }, { label: this.brand?.name || "" }];
            return [{ label: this.heading }];
        },
        activeChips() {
            const q = this.$route.query;
            const chips = [];
            const brandName = (slug) => this.facets.brands?.find((b) => b.slug === slug)?.name || slug;
            if (this.mode !== "brand") String(q.brand || "").split(",").filter(Boolean).forEach((v) => chips.push({ key: "brand", value: v, label: brandName(v) }));
            if (q.min || q.max) chips.push({ key: "price", label: `${q.min ? this.$money(q.min) : this.$money(this.facets.price?.min || 0)} – ${q.max ? this.$money(q.max) : this.$money(this.facets.price?.max || 0)}` });
            if (q.stock) chips.push({ key: "stock", label: this.$t("In stock") });
            if (q.sale && this.mode !== "deals") chips.push({ key: "sale", label: this.$t("On sale") });
            if (q.rating) chips.push({ key: "rating", label: this.$t("{n}★ & up", { n: q.rating }) });
            Object.keys(q)
                .filter((k) => !RESERVED.includes(k))
                .forEach((k) => {
                    const spec = this.facets.specs?.find((s) => s.slug === k);
                    String(q[k]).split(",").filter(Boolean).forEach((v) => {
                        const value = spec?.values.find((x) => x.slug === v)?.value || v;
                        chips.push({ key: k, value: v, label: spec ? `${this.$t(spec.name)}: ${value}` : value });
                    });
                });
            return chips;
        },
    },
    watch: {
        "$route.fullPath": { immediate: true, handler: "load" },
    },
    methods: {
        url: fileUrl,
        params() {
            const q = this.$route.query;
            const p = { page: q.page, sort: q.sort, brand: q.brand, min: q.min, max: q.max, stock: q.stock, sale: q.sale, rating: q.rating, q: q.q, per_page: Number(this.$store.state.prefs.settings['display.products_per_page']) || 24 };
            if (this.mode === "category") p.category = this.$route.params.slug;
            if (this.mode === "brand") p.brand_page = this.$route.params.slug;
            if (this.mode === "deals") p.sale = 1;
            Object.keys(q)
                .filter((k) => !RESERVED.includes(k))
                .forEach((k) => (p[`f[${k}]`] = q[k]));
            return Object.fromEntries(Object.entries(p).filter(([, v]) => v !== undefined && v !== null && v !== ""));
        },
        async load() {
            if (!["category", "brand", "search", "deals", "shop"].includes(this.mode)) return;
            const id = ++this.requestId;
            this.loading = true;
            try {
                const { data } = await api.get("/shop", { params: this.params(), quiet: true });
                if (id !== this.requestId) return; // a newer filter change already started
                this.products = data.products.data;
                this.total = data.products.total;
                this.page = data.products.current_page;
                this.lastPage = data.products.last_page;
                this.facets = data.facets;
                this.facetsReady = true;
                this.category = data.category;
                this.brand = data.brand;
                this.setTitle();
            } catch (e) {
                if (id !== this.requestId) return;
                this.products = [];
                this.total = 0;
                if (e.response?.status === 404) this.$router.replace({ path: "/not-found" });
            } finally {
                if (id === this.requestId) this.loading = false;
            }
        },
        setTitle() {
            const site = this.$store.state.prefs.settings["store.name"] || "";
            const custom = this.mode === "category" ? this.category?.meta_title : this.mode === "brand" ? this.brand?.meta_title : "";
            document.title = `${custom || this.heading} · ${site}`;
        },
        go(query) {
            const clean = Object.fromEntries(Object.entries(query).filter(([, v]) => v !== undefined && v !== null && v !== ""));
            this.$router.push({ path: this.$route.path, query: clean });
        },
        goPage(n) {
            this.go({ ...this.$route.query, page: n > 1 ? n : undefined });
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
        removeChip(c) {
            const q = { ...this.$route.query };
            delete q.page;
            if (c.key === "price") {
                delete q.min;
                delete q.max;
            } else if (c.value !== undefined && String(q[c.key] || "").includes(",")) {
                q[c.key] = String(q[c.key]).split(",").filter((v) => v !== c.value).join(",");
            } else {
                delete q[c.key];
            }
            this.go(q);
        },
        clearAll() {
            const keep = {};
            if (this.$route.query.q) keep.q = this.$route.query.q;
            if (this.$route.query.sort) keep.sort = this.$route.query.sort;
            this.go(keep);
        },
    },
};
</script>
